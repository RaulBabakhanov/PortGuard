<?php

namespace App\Http\Controllers\Concerns;

use App\Models\PdfDownloadLog;
use App\Models\Scan;
use App\Models\ScanReport;
use Illuminate\Support\Facades\Auth;

trait LogsPdfDownloads
{
    protected function logPdfDownload(Scan $scan, ScanReport $report, string $actorType): void
    {
        $actor = $actorType === 'admin'
            ? Auth::guard('admin')->user()
            : Auth::user();

        PdfDownloadLog::query()->create([
            'scan_id' => $scan->id,
            'scan_report_id' => $report->id,
            'actor_type' => $actorType,
            'actor_id' => $actor?->id,
            'actor_email' => $actor?->email,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            'content_sha256' => $report->content_sha256,
        ]);
    }
}
