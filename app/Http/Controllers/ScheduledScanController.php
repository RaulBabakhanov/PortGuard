<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\ScheduledScan;
use App\Models\Target;
use App\Services\ActivityLogger;
use App\Services\ScanRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ScheduledScanController extends Controller
{
    public function index(Request $request): View
    {
        $items = ScheduledScan::query()
            ->with('target')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $targets = Target::query()->where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('scheduled.index', compact('items', 'targets'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'target_id' => [
                'required',
                'integer',
                Rule::exists('targets', 'id')->where(fn ($q) => $q->where('user_id', $request->user()->id)),
            ],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'ports' => ['nullable', 'string', 'max:120', 'regex:/^[0-9,\-\s]*$/'],
        ], [
            'target_id.exists' => 'Seçilen hedef bulunamadı.',
            'ports.regex' => 'Port alanı yalnızca sayı, virgül ve tire içerebilir.',
        ]);

        $target = Target::query()->where('user_id', $request->user()->id)->findOrFail($data['target_id']);

        $item = ScheduledScan::query()->create([
            'user_id' => $request->user()->id,
            'target_id' => $target->id,
            'name' => $data['name'],
            'frequency' => $data['frequency'],
            'ports' => $data['ports'] ?: $target->ports,
            'is_active' => true,
            'next_run_at' => now()->addHour(),
        ]);

        $logger->log('schedule.created', "Zamanlanmış tarama eklendi: {$item->name}", $item);

        return back()->with('status', 'Zamanlanmış tarama kaydedildi.');
    }

    public function toggle(Request $request, ScheduledScan $scheduledScan, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($scheduledScan->user_id === $request->user()->id, 403);
        $scheduledScan->update(['is_active' => ! $scheduledScan->is_active]);
        $logger->log(
            $scheduledScan->is_active ? 'schedule.enabled' : 'schedule.disabled',
            'Zamanlanmış tarama '.($scheduledScan->is_active ? 'açıldı' : 'kapatıldı'),
            $scheduledScan
        );

        return back()->with('status', $scheduledScan->is_active ? 'Zamanlama açıldı.' : 'Zamanlama durduruldu.');
    }

    public function runNow(Request $request, ScheduledScan $scheduledScan, ScanRunner $runner, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($scheduledScan->user_id === $request->user()->id, 403);

        $target = $scheduledScan->target;
        if (! $target) {
            return back()->withErrors(['schedule' => 'Hedef bulunamadı.']);
        }

        try {
            set_time_limit(0);

            $scan = Scan::query()->create([
                'user_id' => $request->user()->id,
                'target_id' => $target->id,
                'name' => $scheduledScan->name.' (manuel)',
                'type' => $target->type,
                'ip' => $target->ip,
                'cidr' => $target->cidr,
                'start_ip' => $target->start_ip,
                'end_ip' => $target->end_ip,
                'ports' => $scheduledScan->ports ?: $target->ports,
                'status' => 'pending',
            ]);

            $runner->run($scan);

            $scheduledScan->update([
                'last_run_at' => now(),
                'next_run_at' => $scheduledScan->computeNextRun(),
            ]);

            $logger->log('schedule.run_now', "Zamanlanmış tarama şimdi çalıştırıldı: {$scheduledScan->name}", $scheduledScan, [
                'scan_id' => $scan->id,
            ]);

            return redirect()
                ->route('scans.show', $scan)
                ->with('status', 'Zamanlanmış tarama çalıştırıldı.');
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'schedule' => config('app.debug')
                    ? $e->getMessage()
                    : 'Tarama çalıştırılamadı.',
            ]);
        }
    }

    public function destroy(Request $request, ScheduledScan $scheduledScan, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($scheduledScan->user_id === $request->user()->id, 403);
        $name = $scheduledScan->name;
        $scheduledScan->delete();
        $logger->log('schedule.deleted', "Zamanlanmış tarama silindi: {$name}");

        return back()->with('status', 'Kayıt silindi.');
    }
}
