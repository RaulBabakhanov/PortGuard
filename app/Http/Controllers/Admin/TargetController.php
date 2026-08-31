<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TargetController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $userId = $request->integer('user_id') ?: null;

        $targets = Target::query()
            ->with('user:id,name,email')
            ->withCount('scans')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('ip', 'like', "%{$q}%")
                        ->orWhere('cidr', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.targets.index', compact('targets', 'users', 'q', 'userId'));
    }
}
