<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CveFinding;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CveController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $severity = trim((string) $request->query('severity', ''));
        $service = trim((string) $request->query('service', ''));
        $ip = trim((string) $request->query('ip', ''));
        $userId = $request->integer('user_id') ?: null;

        $cves = CveFinding::query()
            ->select([
                'id', 'user_id', 'scan_id', 'scan_service_id', 'cve_id',
                'service_name', 'severity', 'description', 'created_at',
            ])
            ->with([
                'user:id,name,email',
                'scan:id,name',
                'service:id,scan_host_id,name,product,version,port',
                'service.host:id,ip',
            ])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($severity !== '', fn ($query) => $query->where('severity', $severity))
            ->when($service !== '', fn ($query) => $query->where('service_name', $service))
            ->when($ip !== '', function ($query) use ($ip) {
                $query->whereHas('service.host', fn ($h) => $h->where('ip', 'like', "%{$ip}%"));
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('cve_id', 'like', "%{$q}%")
                        ->orWhere('service_name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('service.host', fn ($h) => $h->where('ip', 'like', "%{$q}%"));
                });
            })
            ->latest('id')
            ->simplePaginate(10)
            ->withQueryString();

        $filters = Cache::remember('admin.cve.filters', 300, fn () => [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'services' => CveFinding::query()->whereNotNull('service_name')->distinct()->orderBy('service_name')->pluck('service_name'),
            'severities' => CveFinding::query()->whereNotNull('severity')->distinct()->orderBy('severity')->pluck('severity'),
        ]);

        $stats = Cache::remember('admin.cve.stats', 120, function () {
            $row = CveFinding::query()
                ->selectRaw("count(*) as total, count(distinct cve_id) as uniq, sum(case when upper(severity) in ('HIGH','CRITICAL') then 1 else 0 end) as high_count")
                ->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'unique' => (int) ($row->uniq ?? 0),
                'high' => (int) ($row->high_count ?? 0),
            ];
        });

        return view('admin.cves.index', [
            'cves' => $cves,
            'users' => $filters['users'],
            'q' => $q,
            'severity' => $severity,
            'service' => $service,
            'ip' => $ip,
            'userId' => $userId,
            'services' => $filters['services'],
            'severities' => $filters['severities'],
            'stats' => $stats,
        ]);
    }
}
