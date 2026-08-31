<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Services\ScanRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunScanJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(public int $scanId) {}

    public function handle(ScanRunner $runner): void
    {
        $scan = Scan::query()->find($this->scanId);
        if (! $scan || $scan->status === 'completed') {
            return;
        }

        $runner->run($scan);
    }
}
