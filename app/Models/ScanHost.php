<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanHost extends Model
{
    protected $fillable = [
        'scan_id', 'ip', 'is_up', 'hostname', 'raw_output',
    ];

    protected function casts(): array
    {
        return ['is_up' => 'boolean'];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ScanService::class);
    }
}
