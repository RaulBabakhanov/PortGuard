<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $logger->log('auth.login', 'Kullanıcı giriş yaptı', Auth::user(), [
            'email' => Auth::user()?->email,
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $logger->log('auth.logout', 'Kullanıcı çıkış yaptı', Auth::user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
