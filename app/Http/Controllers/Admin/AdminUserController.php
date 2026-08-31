<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $admins = AdminUser::query()->latest('id')->paginate(10);

        return view('admin.admins.index', [
            'admins' => $admins,
            'openModal' => session('open_modal') ?? old('_modal') ?? $request->query('open'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:admin_users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.unique' => 'Bu admin e-postası zaten var.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
        ]);

        AdminUser::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('status', 'Admin kullanıcısı oluşturuldu.');
    }

    public function update(Request $request, AdminUser $admin): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('admin_users', 'email')->ignore($admin->id)],
        ], [
            'email.unique' => 'Bu admin e-postası zaten var.',
        ]);

        $admin->update($data);

        return redirect()
            ->route('admin.admins.index', ['open' => 'admin-'.$admin->id])
            ->with('status', 'Admin bilgileri güncellendi.');
    }

    public function toggle(AdminUser $admin): RedirectResponse
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return redirect()
                ->route('admin.admins.index', ['open' => 'admin-'.$admin->id])
                ->withErrors(['admin' => 'Kendi hesabınızı pasifleştiremezsiniz.']);
        }

        $admin->update(['is_active' => ! $admin->is_active]);

        return redirect()
            ->route('admin.admins.index', ['open' => 'admin-'.$admin->id])
            ->with('status', $admin->is_active ? 'Admin aktifleştirildi.' : 'Admin pasifleştirildi.');
    }

    public function destroy(AdminUser $admin): RedirectResponse
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return redirect()
                ->route('admin.admins.index', ['open' => 'admin-'.$admin->id])
                ->withErrors(['admin' => 'Kendi hesabınızı silemezsiniz.']);
        }

        if (AdminUser::query()->count() <= 1) {
            return redirect()
                ->route('admin.admins.index', ['open' => 'admin-'.$admin->id])
                ->withErrors(['admin' => 'Son admin silinemez.']);
        }

        $admin->delete();

        return redirect()
            ->route('admin.admins.index')
            ->with('status', 'Admin silindi.');
    }

    public function updatePassword(Request $request, AdminUser $admin): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
        ]);

        $admin->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('admin.admins.index', ['open' => 'admin-'.$admin->id])
            ->with('status', 'Şifre güncellendi.');
    }
}
