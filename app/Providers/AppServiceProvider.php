<?php

namespace App\Providers;

use App\Models\UserNotification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.pg');
        Paginator::defaultSimpleView('vendor.pagination.pg-simple');

        Password::defaults(static fn () => Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());

        if ($root = config('app.url')) {
            URL::forceRootUrl(rtrim($root, '/'));
        }

        View::composer('layouts.sidebar', function ($view): void {
            $user = Auth::user();
            $unread = 0;

            if ($user) {
                $unread = Cache::remember(
                    'pg.unread.'.$user->id,
                    45,
                    fn () => UserNotification::query()
                        ->where('user_id', $user->id)
                        ->whereNull('read_at')
                        ->count()
                );
            }

            $view->with([
                'pgUser' => $user,
                'pgUnread' => $unread,
            ]);
        });
    }
}
