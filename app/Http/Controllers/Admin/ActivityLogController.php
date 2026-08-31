<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $action = trim((string) $request->query('action', ''));
        $userId = $request->integer('user_id') ?: null;

        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('description', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%")
                        ->orWhere('ip_address', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action');
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.logs.index', compact('logs', 'actions', 'users', 'q', 'action', 'userId'));
    }
}
