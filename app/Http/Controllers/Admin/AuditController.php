<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\MunicipalitySetting;
use App\Models\PdfDownloadLog;
use App\Models\ScanReport;
use App\Services\AdminActivityLogger;
use App\Services\ScanComparisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuditController extends Controller
{
    private const TABS = ['admin_logs', 'downloads', 'reports'];

    public function index(Request $request): View
    {
        $settings = MunicipalitySetting::current();
        $retention = (int) $settings->kvkk_retention_days;
        $tab = $request->query('tab', 'admin_logs');
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'admin_logs';
        }

        $adminLogs = null;
        $downloads = null;
        $immutableReports = null;

        match ($tab) {
            'downloads' => $downloads = PdfDownloadLog::query()
                ->select([
                    'id', 'scan_id', 'actor_type', 'actor_email',
                    'ip_address', 'content_sha256', 'created_at',
                ])
                ->with('scan:id,name')
                ->latest('id')
                ->simplePaginate(15)
                ->withQueryString(),
            'reports' => $immutableReports = ScanReport::query()
                ->listMeta()
                ->with('scan:id,name')
                ->latest('id')
                ->simplePaginate(15)
                ->withQueryString(),
            default => $adminLogs = AdminActivityLog::query()
                ->select([
                    'id', 'admin_user_id', 'action', 'description', 'ip_address', 'created_at',
                ])
                ->with('admin:id,email')
                ->latest('id')
                ->simplePaginate(15)
                ->withQueryString(),
        };

        $expiringSoon = Cache::remember(
            'audit.expiring_reports.'.$retention,
            300,
            fn () => ScanReport::query()
                ->where('created_at', '<', now()->subDays(max(1, $retention - 30)))
                ->count()
        );

        return view('admin.audit.index', [
            'settings' => $settings,
            'tab' => $tab,
            'adminLogs' => $adminLogs,
            'downloads' => $downloads,
            'immutableReports' => $immutableReports,
            'retentionCutoff' => now()->subDays($retention),
            'expiringSoon' => $expiringSoon,
        ]);
    }

    public function updateSettings(Request $request, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'kvkk_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'approval_host_threshold' => ['required', 'integer', 'min:2', 'max:256'],
            'enforce_allowed_networks' => ['nullable', 'boolean'],
            'require_approval_for_critical' => ['nullable', 'boolean'],
        ]);

        $settings = MunicipalitySetting::current();
        $settings->update([
            'kvkk_retention_days' => $data['kvkk_retention_days'],
            'approval_host_threshold' => $data['approval_host_threshold'],
            'enforce_allowed_networks' => $request->boolean('enforce_allowed_networks'),
            'require_approval_for_critical' => $request->boolean('require_approval_for_critical'),
        ]);

        $logger->log('settings.updated', 'Belediye denetim ayarları güncellendi', $settings, $data);

        return back()->with('status', 'Denetim ayarları kaydedildi.');
    }

    public function comparison(Request $request, ScanComparisonService $comparison): View
    {
        $days = (int) $request->query('days', 30);

        return view('admin.audit.comparison', [
            'diff' => $comparison->compareRolling($days),
            'days' => in_array($days, ScanComparisonService::WINDOWS, true) ? $days : 30,
            'windows' => ScanComparisonService::WINDOWS,
        ]);
    }
}
