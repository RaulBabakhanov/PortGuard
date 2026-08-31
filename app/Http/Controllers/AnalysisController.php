<?php

namespace App\Http\Controllers;

use App\Models\CveFinding;
use App\Models\Scan;
use App\Models\ScanService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalysisController extends Controller
{
    public function services(Request $request): View
    {
        $userId = $request->user()->id;
        $q = trim((string) $request->query('q', ''));
        $name = trim((string) $request->query('name', ''));

        $services = ScanService::query()
            ->select(
                'name',
                DB::raw('count(*) as total'),
                DB::raw('count(distinct scan_id) as scan_total'),
                DB::raw('count(distinct scan_host_id) as host_total')
            )
            ->whereHas('scan', fn ($query) => $query->where('user_id', $userId))
            ->when($name !== '', fn ($query) => $query->where('name', $name))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('product', 'like', "%{$q}%")
                        ->orWhere('version', 'like', "%{$q}%");
                });
            })
            ->groupBy('name')
            ->orderByDesc('total')
            ->get();

        $latest = ScanService::query()
            ->with(['host:id,ip', 'scan:id,name'])
            ->withCount('cveFindings')
            ->whereHas('scan', fn ($query) => $query->where('user_id', $userId))
            ->when($name !== '', fn ($query) => $query->where('name', $name))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('product', 'like', "%{$q}%")
                        ->orWhere('version', 'like', "%{$q}%")
                        ->orWhereHas('host', fn ($h) => $h->where('ip', 'like', "%{$q}%"));
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $names = ScanService::query()
            ->whereHas('scan', fn ($query) => $query->where('user_id', $userId))
            ->distinct()
            ->orderBy('name')
            ->pluck('name');

        return view('analysis.services', compact('services', 'latest', 'names', 'q', 'name'));
    }

    public function cves(Request $request): View
    {
        $userId = $request->user()->id;
        $q = trim((string) $request->query('q', ''));
        $severity = trim((string) $request->query('severity', ''));
        $service = trim((string) $request->query('service', ''));
        $ip = trim((string) $request->query('ip', ''));

        $cves = CveFinding::query()
            ->with([
                'scan:id,name',
                'service:id,scan_host_id,name,product,version,port',
                'service.host:id,ip',
            ])
            ->where('user_id', $userId)
            ->when($severity !== '', fn ($query) => $query->where('severity', $severity))
            ->when($service !== '', fn ($query) => $query->where('service_name', $service))
            ->when($ip !== '', function ($query) use ($ip) {
                $query->whereHas('service.host', fn ($h) => $h->where('ip', 'like', "%{$ip}%"));
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('cve_id', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('service_name', 'like', "%{$q}%")
                        ->orWhereHas('service.host', fn ($h) => $h->where('ip', 'like', "%{$q}%"));
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $severities = Cache::remember("pg.cve.severities.{$userId}", 60, fn () => CveFinding::query()
            ->where('user_id', $userId)
            ->whereNotNull('severity')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity')
            ->values()
            ->all());

        $services = Cache::remember("pg.cve.services.{$userId}", 60, fn () => CveFinding::query()
            ->where('user_id', $userId)
            ->whereNotNull('service_name')
            ->distinct()
            ->orderBy('service_name')
            ->pluck('service_name')
            ->values()
            ->all());

        $stats = Cache::remember("pg.cve.stats.{$userId}", 30, function () use ($userId) {
            $row = CveFinding::query()
                ->where('user_id', $userId)
                ->selectRaw("count(*) as total, count(distinct cve_id) as uniq, sum(case when upper(severity) in ('HIGH','CRITICAL') then 1 else 0 end) as high_count")
                ->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'high' => (int) ($row->high_count ?? 0),
                'unique' => (int) ($row->uniq ?? 0),
            ];
        });

        return view('analysis.cves', compact('cves', 'severities', 'services', 'q', 'severity', 'service', 'ip', 'stats'));
    }

    public function reports(Request $request): View
    {
        $userId = $request->user()->id;
        $from = $request->query('from');
        $to = $request->query('to');
        $scanId = $request->integer('scan_id') ?: null;

        $scansQuery = Scan::query()->where('user_id', $userId)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $scanList = (clone $scansQuery)->latest()->limit(50)->get();

        return view('analysis.reports', [
            'from' => $from,
            'to' => $to,
            'scanId' => $scanId,
            'scanList' => $scanList,
            'byStatus' => (clone $scansQuery)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'severityStats' => CveFinding::query()
                ->select('severity', DB::raw('count(*) as total'))
                ->where('user_id', $userId)
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->when($scanId, fn ($q) => $q->where('scan_id', $scanId))
                ->groupBy('severity')
                ->orderByDesc('total')
                ->get(),
            'topServices' => ScanService::query()
                ->select('name', DB::raw('count(*) as total'))
                ->whereHas('scan', function ($q) use ($userId, $from, $to, $scanId) {
                    $q->where('user_id', $userId)
                        ->when($from, fn ($qq) => $qq->whereDate('created_at', '>=', $from))
                        ->when($to, fn ($qq) => $qq->whereDate('created_at', '<=', $to))
                        ->when($scanId, fn ($qq) => $qq->where('id', $scanId));
                })
                ->groupBy('name')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'topCves' => CveFinding::query()
                ->select('cve_id', DB::raw('count(*) as total'))
                ->where('user_id', $userId)
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->when($scanId, fn ($q) => $q->where('scan_id', $scanId))
                ->groupBy('cve_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'recentScans' => (clone $scansQuery)->latest()->limit(15)->get(),
            'totals' => [
                'scans' => (clone $scansQuery)->count(),
                'cves' => CveFinding::query()
                    ->where('user_id', $userId)
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->when($scanId, fn ($q) => $q->where('scan_id', $scanId))
                    ->count(),
                'services' => ScanService::query()
                    ->whereHas('scan', function ($q) use ($userId, $from, $to, $scanId) {
                        $q->where('user_id', $userId)
                            ->when($from, fn ($qq) => $qq->whereDate('created_at', '>=', $from))
                            ->when($to, fn ($qq) => $qq->whereDate('created_at', '<=', $to))
                            ->when($scanId, fn ($qq) => $qq->where('id', $scanId));
                    })
                    ->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $userId = $request->user()->id;
        $type = $request->query('type', 'cves');
        $from = $request->query('from');
        $to = $request->query('to');
        $scanId = $request->integer('scan_id') ?: null;

        $filename = 'portguard-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($type, $userId, $from, $to, $scanId) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if ($type === 'scans') {
                fputcsv($out, ['ID', 'Ad', 'Hedef', 'Durum', 'Host', 'Aktif', 'Servis', 'CVE', 'Portlar', 'Tarih'], ';');
                Scan::query()
                    ->where('user_id', $userId)
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->when($scanId, fn ($q) => $q->where('id', $scanId))
                    ->latest()
                    ->chunk(100, function ($rows) use ($out) {
                        foreach ($rows as $scan) {
                            fputcsv($out, [
                                $scan->id,
                                $scan->name,
                                $scan->summaryLabel(),
                                $scan->status,
                                $scan->total_hosts,
                                $scan->active_hosts,
                                $scan->service_count,
                                $scan->cve_count,
                                $scan->ports,
                                optional($scan->created_at)->format('Y-m-d H:i:s'),
                            ], ';');
                        }
                    });
            } elseif ($type === 'services') {
                fputcsv($out, ['Servis', 'Ürün', 'Versiyon', 'Port', 'Host', 'Tarama', 'Tarih'], ';');
                ScanService::query()
                    ->with(['host', 'scan'])
                    ->whereHas('scan', function ($q) use ($userId, $from, $to, $scanId) {
                        $q->where('user_id', $userId)
                            ->when($from, fn ($qq) => $qq->whereDate('created_at', '>=', $from))
                            ->when($to, fn ($qq) => $qq->whereDate('created_at', '<=', $to))
                            ->when($scanId, fn ($qq) => $qq->where('id', $scanId));
                    })
                    ->latest()
                    ->chunk(100, function ($rows) use ($out) {
                        foreach ($rows as $row) {
                            fputcsv($out, [
                                $row->name,
                                $row->product,
                                $row->version,
                                $row->port,
                                $row->host?->ip,
                                $row->scan_id,
                                optional($row->created_at)->format('Y-m-d H:i:s'),
                            ], ';');
                        }
                    });
            } else {
                fputcsv($out, ['CVE', 'Servis', 'Severity', 'Tarama', 'Host', 'Açıklama', 'Tarih'], ';');
                CveFinding::query()
                    ->with(['service.host'])
                    ->where('user_id', $userId)
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->when($scanId, fn ($q) => $q->where('scan_id', $scanId))
                    ->latest()
                    ->chunk(100, function ($rows) use ($out) {
                        foreach ($rows as $cve) {
                            fputcsv($out, [
                                $cve->cve_id,
                                $cve->service_name,
                                $cve->severity,
                                $cve->scan_id,
                                $cve->service?->host?->ip,
                                $cve->description,
                                optional($cve->created_at)->format('Y-m-d H:i:s'),
                            ], ';');
                        }
                    });
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
