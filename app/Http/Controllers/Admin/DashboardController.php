<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\CveFinding;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $payload = Cache::remember('admin.dashboard', 45, function () {
            return [
                'stats' => [
                    'users' => User::query()->count(),
                    'admins' => AdminUser::query()->count(),
                    'scans' => Scan::query()->count(),
                    'cves' => CveFinding::query()->count(),
                    'logs' => ActivityLog::query()->count(),
                ],
                'recentUsers' => User::query()->latest('id')->limit(5)->get(['id', 'name', 'email', 'created_at']),
                'recentLogs' => ActivityLog::query()
                    ->select(['id', 'user_id', 'action', 'description', 'created_at'])
                    ->with('user:id,name,email')
                    ->latest('id')
                    ->limit(10)
                    ->get(),
                'recentScans' => Scan::query()
                    ->select(['id', 'user_id', 'name', 'status', 'created_at'])
                    ->with('user:id,name,email')
                    ->latest('id')
                    ->limit(8)
                    ->get(),
            ];
        });

        return view('admin.dashboard', $payload);
    }
}
