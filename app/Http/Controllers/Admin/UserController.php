<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Scan;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $departmentId = $request->integer('department_id') ?: null;

        $users = User::query()
            ->with('department:id,name')
            ->withCount(['scans', 'activityLogs', 'cveFindings', 'targets'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'departmentId' => $departmentId,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'openModal' => session('open_modal') ?? old('_modal') ?? $request->query('open'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.users.index', ['open' => 'user-create']);
    }

    public function store(Request $request, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'department_id' => ['nullable', 'exists:departments,id'],
        ], [
            'email.unique' => 'Bu e-posta zaten kayıtlı.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'department_id' => $data['department_id'] ?? null,
            'email_verified_at' => now(),
        ]);

        UserSetting::query()->firstOrCreate(['user_id' => $user->id]);
        $logger->log('user.created', "Panel kullanıcısı oluşturuldu: {$user->email}", $user);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Panel kullanıcısı oluşturuldu.');
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.index', ['open' => 'user-'.$user->id]);
    }

    public function edit(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.index', ['open' => 'user-'.$user->id]);
    }

    public function update(Request $request, User $user, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'department_id' => ['nullable', 'exists:departments,id'],
        ], [
            'email.unique' => 'Bu e-posta zaten kayıtlı.',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'department_id' => $data['department_id'] ?: null,
        ]);

        $logger->log('user.updated', "Panel kullanıcısı güncellendi: {$user->email}", $user);

        return redirect()
            ->route('admin.users.index', ['open' => 'user-'.$user->id])
            ->with('status', 'Kullanıcı bilgileri güncellendi.');
    }

    public function updatePassword(Request $request, User $user, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $logger->log('user.password_reset', "Panel kullanıcısı şifresi sıfırlandı: {$user->email}", $user);

        return redirect()
            ->route('admin.users.index', ['open' => 'user-'.$user->id])
            ->with('status', 'Şifre güncellendi.');
    }

    public function destroy(User $user, AdminActivityLogger $logger): RedirectResponse
    {
        $email = $user->email;
        $user->delete();
        $logger->log('user.deleted', "Panel kullanıcısı silindi: {$email}");

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Kullanıcı silindi: {$email}");
    }
}
