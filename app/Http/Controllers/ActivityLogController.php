<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $action = trim((string) $request->query('action', ''));

        $logs = ActivityLog::query()
            ->where('user_id', $request->user()->id)
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('description', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%")
                        ->orWhere('ip_address', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $actions = ActivityLog::query()
            ->where('user_id', $request->user()->id)
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('activity.index', compact('logs', 'actions', 'q', 'action'));
    }
}
