<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CveFinding extends Model
{
    protected $fillable = [
        'user_id', 'scan_id', 'scan_service_id', 'service_name',
        'cve_id', 'description', 'severity', 'raw',
    ];

    protected function casts(): array
    {
        return ['raw' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ScanService::class, 'scan_service_id');
    }
}
