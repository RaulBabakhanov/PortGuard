<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('yonetim') || $request->is('yonetim/*')
                ? route('admin.login')
                : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            return $request->is('yonetim') || $request->is('yonetim/*')
                ? route('admin.dashboard')
                : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\InvalidArgumentException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            if ($request->isMethod('GET')) {
                return response()->view('errors.simple', [
                    'title' => 'Geçersiz istek',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['ip' => $e->getMessage()]);
        });

        // APP_DEBUG=true iken shared hosting highlight_file'ı kapatmışsa Symfony sayfası çökmesin
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! config('app.debug') || function_exists('highlight_file')) {
                return null;
            }

            report($e);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sunucu hatası'], 500);
            }

            return response()->view('errors.simple', [
                'title' => 'Sunucu hatası',
                'message' => 'İşlem sırasında bir hata oluştu. Lütfen tekrar deneyin.',
            ], 500);
        });
    })->create();
