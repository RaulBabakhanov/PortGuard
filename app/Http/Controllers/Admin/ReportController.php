<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CveFinding;
use App\Models\Scan;
use App\Models\ScanService;
use App\Models\Target;
use App\Models\User;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $byStatus = Scan::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.reports.index', [
            'totals' => [
                'users' => User::query()->count(),
                'scans' => Scan::query()->count(),
                'services' => ScanService::query()->count(),
                'cves' => CveFinding::query()->count(),
                'targets' => Target::query()->count(),
            ],
            'byStatus' => $byStatus,
            'topUsers' => User::query()
                ->withCount('scans')
                ->orderByDesc('scans_count')
                ->limit(10)
                ->get(['id', 'name', 'email']),
        ]);
    }
}
