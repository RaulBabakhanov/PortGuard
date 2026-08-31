<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Models\User;
use App\Services\AdminActivityLogger;
use App\Services\ExecutiveDashboardService;
use App\Services\ScanPdfStore;
use App\Services\ScanResultPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ScanController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsPdfDownloads;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $userId = $request->integer('user_id') ?: null;

        $scans = Scan::query()
            ->with(['user:id,name,email', 'report:id,scan_id,content_sha256,byte_size,filename,created_at'])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('ip', 'like', "%{$q}%")
                        ->orWhere('cidr', 'like', "%{$q}%")
                        ->orWhere('start_ip', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.scans.index', compact('scans', 'users', 'q', 'status', 'userId'));
    }

    public function show(Request $request, Scan $scan, ScanResultPresenter $presenter, ExecutiveDashboardService $executive): View
    {
        $scan->load([
            'user:id,name,email',
            'report:id,scan_id,content_sha256,byte_size,filename,created_at,content_hmac,created_by_admin_id',
            'approvedByAdmin:id,name,email',
        ]);
        $data = $presenter->present($scan);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $allHosts = $data['onlineHosts'];
        $pageHosts = $allHosts->forPage($page, $perPage)->values();

        $hosts = new LengthAwarePaginator(
            $pageHosts,
            $allHosts->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $assetsByIp = \App\Models\Asset::query()
            ->with('department:id,name')
            ->where('is_active', true)
            ->whereIn('ip', $allHosts->pluck('ip')->all() ?: ['__none__'])
            ->get()
            ->groupBy('ip');

        return view('admin.scans.show', [
            'scan' => $scan,
            'onlineHosts' => $pageHosts,
            'hosts' => $hosts,
            'stats' => $data['stats'],
            'hostBadges' => $data['hostBadges'],
            'hostCves' => $data['hostCves'],
            'report' => $scan->report,
            'assetsByIp' => $assetsByIp,
        ]);
    }

    public function storePdf(Request $request, Scan $scan, ScanPdfStore $store, AdminActivityLogger $logger): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $force = $request->boolean('force');

        try {
            $report = $store->generateAndStore($scan, $admin, $force);
        } catch (RuntimeException $e) {
            return back()->withErrors(['pdf' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['pdf' => 'PDF kaydedilemedi.']);
        }

        $logger->log('pdf.stored', 'PDF arşivlendi (#'.$scan->id.') hash '.$report->shortHash(), $scan, [
            'sha256' => $report->content_sha256,
        ]);

        return back()->with('status', $force
            ? 'PDF yeniden üretildi (şifreli disk + hash DB). Hash: '.$report->shortHash()
            : 'PDF kaydedildi (şifreli disk + hash DB). Hash: '.$report->shortHash());
    }

    public function pdf(Request $request, Scan $scan, ScanPdfStore $store): SymfonyResponse|RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $force = $request->boolean('force');

        try {
            $report = $store->generateAndStore($scan, $admin, $force);
            $binary = $store->decryptVerified($report);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.scans.show', $scan)
                ->withErrors(['pdf' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.scans.show', $scan)
                ->withErrors(['pdf' => 'PDF indirilemedi.']);
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $this->logPdfDownload($scan, $report, 'admin');

        return response()->streamDownload(
            function () use ($binary) {
                echo $binary;
            },
            $report->filename,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-store, private',
            ]
        );
    }

    public function destroy(Scan $scan, AdminActivityLogger $logger): RedirectResponse
    {
        $id = $scan->id;
        $path = $scan->report?->storage_path;
        $scan->delete();

        if ($path) {
            $full = storage_path('app/private/'.$path);
            if (is_file($full)) {
                @unlink($full);
            }
        }

        $logger->log('scan.deleted', "Tarama silindi (#{$id})");

        return redirect()
            ->route('admin.scans.index')
            ->with('status', "Tarama #{$id} silindi.");
    }
}
