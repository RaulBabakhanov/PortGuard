<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id', 'default_ports', 'notify_on_scan_complete',
        'notify_on_cve_found', 'max_hosts_per_scan',
    ];

    protected function casts(): array
    {
        return [
            'notify_on_scan_complete' => 'boolean',
            'notify_on_cve_found' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
