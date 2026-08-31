<?php

use App\Jobs\RunScanJob;
use App\Models\Scan;
use App\Models\ScheduledScan;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $due = ScheduledScan::query()
        ->with('target')
        ->where('is_active', true)
        ->where(function ($q) {
            $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
        })
        ->limit(20)
        ->get();

    $logger = app(ActivityLogger::class);

    foreach ($due as $item) {
        $target = $item->target;
        if (! $target) {
            continue;
        }

        $scan = Scan::query()->create([
            'user_id' => $item->user_id,
            'target_id' => $target->id,
            'name' => $item->name.' (zamanlanmış)',
            'type' => $target->type,
            'ip' => $target->ip,
            'cidr' => $target->cidr,
            'start_ip' => $target->start_ip,
            'end_ip' => $target->end_ip,
            'ports' => $item->ports ?: $target->ports,
            'status' => 'pending',
        ]);

        RunScanJob::dispatch($scan->id);

        $item->update([
            'last_run_at' => now(),
            'next_run_at' => $item->computeNextRun(),
        ]);

        $logger->log('schedule.ran', "Zamanlanmış tarama çalıştırıldı: {$item->name}", $item, [], $item->user);
    }
})->everyFiveMinutes()->name('portguard-scheduled-scans')->withoutOverlapping();
