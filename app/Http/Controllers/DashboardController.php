<?php

namespace App\Http\Controllers;

use App\Models\CveFinding;
use App\Models\Scan;
use App\Models\ScanService;
use App\Models\Target;
use App\Services\NmapScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, NmapScanner $nmap): View
    {
        $user = $request->user();
        $uid = $user->id;

        $stats = Cache::remember("pg.dash.stats.{$uid}", 30, function () use ($uid) {
            return [
                'scans' => Scan::query()->where('user_id', $uid)->count(),
                'targets' => Target::query()->where('user_id', $uid)->count(),
                'services' => ScanService::query()->whereHas('scan', fn ($q) => $q->where('user_id', $uid))->count(),
                'cves' => CveFinding::query()->where('user_id', $uid)->count(),
                'unread' => Cache::remember(
                    'pg.unread.'.$uid,
                    45,
                    fn () => \App\Models\UserNotification::query()
                        ->where('user_id', $uid)
                        ->whereNull('read_at')
                        ->count()
                ),
            ];
        });

        return view('dashboard', [
            'stats' => $stats,
            'recentScans' => Scan::query()
                ->where('user_id', $uid)
                ->latest('id')
                ->limit(5)
                ->get(['id', 'name', 'status', 'type', 'ip', 'cidr', 'start_ip', 'end_ip', 'cve_count', 'created_at']),
            'recentCves' => CveFinding::query()
                ->where('user_id', $uid)
                ->latest('id')
                ->limit(5)
                ->get(['id', 'cve_id', 'service_name', 'severity', 'scan_id', 'created_at']),
            'nmap' => $nmap->status(),
        ]);
    }
}
