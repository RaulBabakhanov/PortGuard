<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledScan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduledController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->integer('user_id') ?: null;

        $items = ScheduledScan::query()
            ->with('user:id,name,email')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.scheduled.index', compact('items', 'users', 'userId'));
    }
}
