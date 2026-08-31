<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScanService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $userId = $request->integer('user_id') ?: null;

        $services = ScanService::query()
            ->with(['scan:id,name,user_id', 'scan.user:id,name,email'])
            ->when($userId, fn ($query) => $query->whereHas('scan', fn ($s) => $s->where('user_id', $userId)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('product', 'like', "%{$q}%")
                        ->orWhere('version', 'like', "%{$q}%")
                        ->orWhere('port', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.services.index', compact('services', 'users', 'q', 'userId'));
    }
}
