<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = UserSetting::query()->firstOrCreate(['user_id' => $request->user()->id]);

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'default_ports' => ['required', 'string', 'max:120', 'regex:/^[0-9,\-\s]+$/'],
            'max_hosts_per_scan' => ['required', 'integer', 'min:1', 'max:256'],
            'notify_on_scan_complete' => ['nullable', 'boolean'],
            'notify_on_cve_found' => ['nullable', 'boolean'],
        ]);

        $settings = UserSetting::query()->firstOrCreate(['user_id' => $request->user()->id]);
        $settings->update([
            'default_ports' => preg_replace('/\s+/', '', $data['default_ports']),
            'max_hosts_per_scan' => $data['max_hosts_per_scan'],
            'notify_on_scan_complete' => $request->boolean('notify_on_scan_complete'),
            'notify_on_cve_found' => $request->boolean('notify_on_cve_found'),
        ]);

        $logger->log('settings.updated', 'Kullanıcı ayarları güncellendi', $settings, $data);

        return back()->with('status', 'Ayarlar kaydedildi.');
    }
}
