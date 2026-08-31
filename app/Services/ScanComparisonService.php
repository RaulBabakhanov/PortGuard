<?php

namespace App\Services;

use App\Models\CveFinding;
use App\Models\Scan;
use App\Models\ScanService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ScanComparisonService
{
    public const WINDOWS = [7, 14, 30, 90];

    /**
     * Son N günü, hemen önceki N günle karşılaştırır (ay bitimini beklemez).
     *
     * @return array{
     *   days: int,
     *   period_a: array{label: string, from: string, to: string, scans: int, services: int, cves: int, critical_cves: int, unique_services: int, unique_cves: int},
     *   period_b: array{label: string, to: string, from: string, scans: int, services: int, cves: int, critical_cves: int, unique_services: int, unique_cves: int},
     *   deltas: array{scans: int, services: int, cves: int, critical_cves: int},
     *   new_services: Collection,
     *   gone_services: Collection,
     *   new_cves: Collection,
     *   gone_cves: Collection,
     *   summary: string
     * }
     */
    public function compareRolling(int $days = 30, ?CarbonInterface $now = null): array
    {
        $days = in_array($days, self::WINDOWS, true) ? $days : 30;
        $now = ($now ?? now())->copy();
        $cacheKey = 'compare.rolling.'.$days.'.'.$now->format('Y-m-d-H');

        return Cache::remember($cacheKey, 120, fn () => $this->buildComparison($days, $now));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildComparison(int $days, CarbonInterface $now): array
    {
        // B = son N gün (şu ana kadar), A = ondan önceki N gün
        $bTo = $now->copy();
        $bFrom = $now->copy()->subDays($days - 1)->startOfDay();
        $aTo = $bFrom->copy()->subSecond();
        $aFrom = $aTo->copy()->subDays($days - 1)->startOfDay();

        $servicesA = $this->serviceFingerprints($aFrom, $aTo);
        $servicesB = $this->serviceFingerprints($bFrom, $bTo);
        $cvesA = $this->cveFingerprints($aFrom, $aTo);
        $cvesB = $this->cveFingerprints($bFrom, $bTo);

        $statsA = $this->periodStats($aFrom, $aTo, "Önceki {$days} gün", $servicesA->count(), $cvesA->count());
        $statsB = $this->periodStats($bFrom, $bTo, "Son {$days} gün", $servicesB->count(), $cvesB->count());

        $keysA = $servicesA->keys();
        $keysB = $servicesB->keys();
        $cveKeysA = $cvesA->keys();
        $cveKeysB = $cvesB->keys();

        $newServices = $keysB->diff($keysA)->map(fn ($k) => $servicesB[$k])->values();
        $goneServices = $keysA->diff($keysB)->map(fn ($k) => $servicesA[$k])->values();
        $newCves = $cveKeysB->diff($cveKeysA)->map(fn ($k) => $cvesB[$k])->values()
            ->sortBy(fn ($c) => $this->severityRank($c['severity']))
            ->values();
        $goneCves = $cveKeysA->diff($cveKeysB)->map(fn ($k) => $cvesA[$k])->values();

        $deltas = [
            'scans' => $statsB['scans'] - $statsA['scans'],
            'services' => $statsB['services'] - $statsA['services'],
            'cves' => $statsB['cves'] - $statsA['cves'],
            'critical_cves' => $statsB['critical_cves'] - $statsA['critical_cves'],
        ];

        return [
            'days' => $days,
            'period_a' => $statsA,
            'period_b' => $statsB,
            'deltas' => $deltas,
            'new_services' => $newServices,
            'gone_services' => $goneServices,
            'new_cves' => $newCves,
            'gone_cves' => $goneCves,
            'summary' => $this->buildSummary($days, $deltas, $newServices->count(), $goneServices->count(), $newCves->count(), $goneCves->count()),
        ];
    }

    /** @deprecated Use compareRolling() */
    public function compareMonths(?CarbonInterface $currentMonth = null): array
    {
        return $this->compareRolling(30, $currentMonth);
    }

    /**
     * @return array{label: string, from: string, to: string, scans: int, services: int, cves: int, critical_cves: int, unique_services: int, unique_cves: int}
     */
    private function periodStats(
        CarbonInterface $from,
        CarbonInterface $to,
        string $label,
        int $uniqueServices = 0,
        int $uniqueCves = 0
    ): array {
        $scanIds = $this->completedScanIds($from, $to);

        return [
            'label' => $label,
            'from' => $from->format('d.m.Y H:i'),
            'to' => $to->format('d.m.Y H:i'),
            'scans' => $scanIds->count(),
            'services' => $scanIds->isEmpty() ? 0 : ScanService::query()->whereIn('scan_id', $scanIds)->count(),
            'cves' => $scanIds->isEmpty() ? 0 : CveFinding::query()->whereIn('scan_id', $scanIds)->count(),
            'critical_cves' => $scanIds->isEmpty() ? 0 : CveFinding::query()
                ->whereIn('scan_id', $scanIds)
                ->whereRaw("UPPER(severity) IN ('CRITICAL','HIGH')")
                ->count(),
            'unique_services' => $uniqueServices,
            'unique_cves' => $uniqueCves,
        ];
    }

    /**
     * @return Collection<string, array{key: string, name: string, port: string|int|null, version: string}>
     */
    private function serviceFingerprints(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $scanIds = $this->completedScanIds($from, $to);
        if ($scanIds->isEmpty()) {
            return collect();
        }

        return ScanService::query()
            ->whereIn('scan_id', $scanIds)
            ->get(['name', 'product', 'port', 'version'])
            ->mapWithKeys(function ($s) {
                $name = trim((string) ($s->product ?: $s->name ?: 'bilinmeyen'));
                $port = $s->port;
                $version = trim((string) ($s->version ?: ''));
                $key = strtolower($name).'|'.$port.'|'.strtolower($version);

                return [$key => [
                    'key' => $key,
                    'name' => $name,
                    'port' => $port,
                    'version' => $version !== '' ? $version : '—',
                ]];
            });
    }

    /**
     * @return Collection<string, array{cve_id: string, severity: string, service_name: string|null, description: string|null}>
     */
    private function cveFingerprints(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $scanIds = $this->completedScanIds($from, $to);
        if ($scanIds->isEmpty()) {
            return collect();
        }

        return CveFinding::query()
            ->whereIn('scan_id', $scanIds)
            ->orderByDesc('id')
            ->get(['cve_id', 'severity', 'service_name', 'description'])
            ->groupBy(fn ($c) => strtoupper(trim((string) $c->cve_id)))
            ->map(function ($group, $cveId) {
                $best = $group->sortBy(fn ($c) => $this->severityRank($c->severity))->first();

                return [
                    'cve_id' => $cveId,
                    'severity' => strtoupper((string) ($best->severity ?: 'N/A')),
                    'service_name' => $best->service_name,
                    'description' => $best->description ? \Illuminate\Support\Str::limit($best->description, 140) : null,
                ];
            });
    }

    private function completedScanIds(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Scan::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->pluck('id');
    }

    private function severityRank(?string $severity): int
    {
        return match (strtoupper((string) $severity)) {
            'CRITICAL' => 0,
            'HIGH' => 1,
            'MEDIUM' => 2,
            'LOW' => 3,
            default => 9,
        };
    }

    private function buildSummary(int $days, array $deltas, int $newSvc, int $goneSvc, int $newCve, int $goneCve): string
    {
        $parts = ["Son {$days} gün, önceki {$days} güne göre anlık karşılaştırılıyor."];

        if ($deltas['critical_cves'] > 0) {
            $parts[] = 'Kritik/yüksek CVE sayısı +'.$deltas['critical_cves'].' arttı.';
        } elseif ($deltas['critical_cves'] < 0) {
            $parts[] = 'Kritik/yüksek CVE sayısı '.abs($deltas['critical_cves']).' azaldı.';
        } else {
            $parts[] = 'Kritik/yüksek CVE sayısı aynı kaldı.';
        }

        $parts[] = "Yeni servis: {$newSvc}, kaybolan: {$goneSvc}. Yeni CVE: {$newCve}, artık görülmeyen: {$goneCve}.";

        return implode(' ', $parts);
    }
}
