<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScanRequest;
use App\Models\Scan;
use App\Models\Target;
use App\Models\UserSetting;
use App\Services\ActivityLogger;
use App\Services\AllowedNetworkGuard;
use App\Services\NmapScanner;
use App\Services\ScanPdfStore;
use App\Services\ScanResultPresenter;
use App\Services\ScanRunner;
use App\Services\TargetResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ScanController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsPdfDownloads;

    public function create(Request $request, NmapScanner $nmap): View
    {
        $settings = UserSetting::query()->firstOrCreate(['user_id' => $request->user()->id]);

        return view('scans.create', [
            'targets' => Target::query()->where('user_id', $request->user()->id)->latest()->get(),
            'defaultPorts' => $settings->default_ports,
            'nmap' => $nmap->status(),
            'prefill' => Target::query()
                ->where('user_id', $request->user()->id)
                ->find($request->integer('target_id')),
        ]);
    }

    public function store(
        StoreScanRequest $request,
        TargetResolver $resolver,
        NmapScanner $nmap,
        ActivityLogger $logger,
        ScanRunner $runner,
        AllowedNetworkGuard $networkGuard,
    ): RedirectResponse {
        $data = $request->validated();
        $user = $request->user();

        try {
            if (! empty($data['target_id'])) {
                $target = Target::query()->where('user_id', $user->id)->findOrFail($data['target_id']);
                $data['ip'] = $target->ip;
                $data['cidr'] = $target->cidr;
                $data['start_ip'] = $target->start_ip;
                $data['end_ip'] = $target->end_ip;
                $data['ports'] = ! empty($data['ports']) ? $data['ports'] : $target->ports;
                $data['name'] = ! empty($data['name']) ? $data['name'] : $target->name;
            }

            $ports = $nmap->sanitizePorts($data['ports'] ?? '');
            $type = $resolver->detectType($data['ip'] ?? null, $data['cidr'] ?? null, $data['start_ip'] ?? null, $data['end_ip'] ?? null);
            $hosts = $resolver->resolve($data['ip'] ?? null, $data['cidr'] ?? null, $data['start_ip'] ?? null, $data['end_ip'] ?? null);

            $networkGuard->assertAllowed($hosts);
            $approval = $networkGuard->approvalRequirement($hosts);

            $scan = Scan::query()->create([
                'user_id' => $user->id,
                'target_id' => $data['target_id'] ?? null,
                'name' => $data['name'] ?: ('Tarama '.now()->format('d.m.Y H:i')),
                'type' => $type,
                'ip' => $data['ip'] ?? null,
                'cidr' => $data['cidr'] ?? null,
                'start_ip' => $data['start_ip'] ?? null,
                'end_ip' => $data['end_ip'] ?? null,
                'ports' => $ports,
                'status' => $approval['requires'] ? 'awaiting_approval' : 'pending',
                'total_hosts' => count($hosts),
                'requires_approval' => $approval['requires'],
                'approval_status' => $approval['requires'] ? 'pending' : 'none',
                'approval_reason' => $approval['reason'],
            ]);

            $logger->log('scan.created', $approval['requires']
                ? "Tarama onay bekliyor (#{$scan->id})"
                : "Yeni tarama oluşturuldu (#{$scan->id})", $scan, [
                    'hosts' => count($hosts),
                    'ports' => $ports,
                    'requires_approval' => $approval['requires'],
                    'approval_reason' => $approval['reason'],
                ]);

            if ($approval['requires']) {
                return redirect()
                    ->route('scans.show', $scan)
                    ->with('status', 'Tarama onay kuyruğuna alındı: '.$approval['reason']);
            }

            set_time_limit(0);
            $runner->run($scan);
            $scan->refresh();

            return redirect()
                ->route('scans.show', $scan)
                ->with('status', $scan->status === 'failed'
                    ? ('Tarama tamamlanamadı: '.($scan->error_message ?: 'Bilinmeyen hata'))
                    : 'Tarama tamamlandı.');
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['ip' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? ('Tarama başlatılamadı: '.$e->getMessage())
                : 'Tarama başlatılamadı. Lütfen bilgileri kontrol edip tekrar deneyin.';

            return back()
                ->withInput()
                ->withErrors(['ip' => $message]);
        }
    }

    public function index(Request $request): View
    {
        $scans = Scan::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('scans.index', compact('scans'));
    }

    public function show(Request $request, int $scan, ScanResultPresenter $presenter): View
    {
        $scan = Scan::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($scan);

        $data = $presenter->present($scan);
        $assetsByIp = \App\Models\Asset::query()
            ->with('department:id,name')
            ->where('is_active', true)
            ->whereIn('ip', $data['onlineHosts']->pluck('ip')->all() ?: ['__none__'])
            ->get()
            ->groupBy('ip');

        return view('scans.show', [
            'scan' => $scan,
            'onlineHosts' => $data['onlineHosts'],
            'stats' => $data['stats'],
            'hostBadges' => $data['hostBadges'],
            'hostCves' => $data['hostCves'],
            'assetsByIp' => $assetsByIp ?? collect(),
        ]);
    }

    public function pdf(Request $request, int $scan, ScanPdfStore $store): SymfonyResponse|RedirectResponse
    {
        $model = Scan::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($scan);

        try {
            $report = $store->generateAndStore($model, null, $request->boolean('force'));
            $binary = $store->decryptVerified($report);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('scans.show', $model)
                ->withErrors(['pdf' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('scans.show', $model)
                ->withErrors(['pdf' => 'PDF indirilemedi.']);
        }

        if ($request->hasSession()) {
            $request->session()->save();
        }

        $this->logPdfDownload($model, $report, 'user');

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

    public function destroy(Request $request, int $scan, ActivityLogger $logger): RedirectResponse
    {
        $model = Scan::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($scan);

        $id = $model->id;
        $model->delete();
        $logger->log('scan.deleted', "Tarama silindi (#{$id})");

        return redirect()->route('scans.index')->with('status', 'Tarama silindi.');
    }
}
