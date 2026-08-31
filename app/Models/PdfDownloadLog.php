<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfDownloadLog extends Model
{
    protected $fillable = [
        'scan_id', 'scan_report_id', 'actor_type', 'actor_id',
        'actor_email', 'ip_address', 'user_agent', 'content_sha256',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ScanReport::class, 'scan_report_id');
    }
}
