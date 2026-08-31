<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markRead();
        Cache::forget('pg.unread.'.$request->user()->id);
        Cache::forget('pg.dash.stats.'.$request->user()->id);

        $scanId = data_get($notification->data, 'scan_id');
        if ($scanId) {
            return redirect()->route('scans.show', $scanId);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget('pg.unread.'.$request->user()->id);
        Cache::forget('pg.dash.stats.'.$request->user()->id);

        return back()->with('status', 'Bildirimler okundu olarak işaretlendi.');
    }
}
