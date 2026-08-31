<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanService extends Model
{
    protected $fillable = [
        'scan_host_id', 'scan_id', 'name', 'product', 'version', 'port', 'protocol', 'raw_line',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(ScanHost::class, 'scan_host_id');
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function cveFindings(): HasMany
    {
        return $this->hasMany(CveFinding::class);
    }
}
