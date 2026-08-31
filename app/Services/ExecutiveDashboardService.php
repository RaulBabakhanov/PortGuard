<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\CveFinding;
use App\Models\Department;
use App\Models\Scan;
use App\Models\ScanHost;
use App\Models\ScanService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ExecutiveDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return Cache::remember('admin.executive.dashboard', 120, fn () => $this->compile());
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(): array
    {
        $openServices = ScanService::query()->count();
        $criticalCves = CveFinding::query()
            ->whereIn('severity', ['CRITICAL', 'critical', 'HIGH', 'high'])
            ->count();
        $activeHosts = ScanHost::query()->where('is_up', true)->count();
        $assets = Asset::query()->where('is_active', true)->count();
        $criticalAssets = Asset::query()->where('is_active', true)->whereIn('criticality', ['high', 'critical'])->count();
        $awaiting = Scan::query()->where('status', 'awaiting_approval')->count();

        $byDepartment = Department::query()
            ->where('is_active', true)
            ->withCount([
                'users',
                'assets' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Department $dept) {
                $userIds = $dept->users()->pluck('id');
                $scans = Scan::query()->whereIn('user_id', $userIds)->where('status', 'completed')->count();
                $cves = CveFinding::query()->whereIn('user_id', $userIds)->count();
                $critical = CveFinding::query()
                    ->whereIn('user_id', $userIds)
                    ->whereIn('severity', ['CRITICAL', 'critical', 'HIGH', 'high'])
                    ->count();
                $lastScan = Scan::query()->whereIn('user_id', $userIds)->latest('id')->value('created_at');

                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'users' => $dept->users_count,
                    'assets' => $dept->assets_count,
                    'scans' => $scans,
                    'cves' => $cves,
                    'critical_cves' => $critical,
                    'last_scan' => $lastScan,
                    'behind' => $scans === 0 || ($lastScan && $lastScan->lt(now()->subDays(30))),
                ];
            });

        $assetRisk = Asset::query()
            ->where('is_active', true)
            ->orderByRaw("FIELD(criticality, 'critical','high','medium','low')")
            ->limit(12)
            ->get()
            ->map(function (Asset $asset) {
                $cveCount = CveFinding::query()
                    ->whereIn(
                        'scan_id',
                        \App\Models\ScanHost::query()->where('ip', $asset->ip)->pluck('scan_id')
                    )
                    ->count();

                return [
                    'asset' => $asset,
                    'cve_count' => $cveCount,
                ];
            });

        return [
            'totals' => [
                'open_services' => $openServices,
                'critical_cves' => $criticalCves,
                'active_hosts' => $activeHosts,
                'assets' => $assets,
                'critical_assets' => $criticalAssets,
                'awaiting_approval' => $awaiting,
                'completed_scans' => Scan::query()->where('status', 'completed')->count(),
            ],
            'departments' => $byDepartment,
            'asset_risk' => $assetRisk,
            'recent_critical_cves' => CveFinding::query()
                ->select(['id', 'user_id', 'scan_id', 'cve_id', 'severity', 'service_name', 'description', 'created_at'])
                ->with(['user:id,name,email', 'scan:id,name'])
                ->whereIn('severity', ['CRITICAL', 'critical', 'HIGH', 'high'])
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @return Collection<int, Asset>
     */
    public function assetsForIp(string $ip): Collection
    {
        return Asset::query()
            ->with('department:id,name')
            ->where('is_active', true)
            ->where('ip', $ip)
            ->get();
    }
}
