<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scan extends Model
{
    protected $fillable = [
        'user_id', 'target_id', 'name', 'type', 'ip', 'cidr', 'start_ip', 'end_ip', 'ports',
        'status', 'total_hosts', 'active_hosts', 'service_count', 'cve_count',
        'error_message', 'started_at', 'finished_at',
        'approval_status', 'approved_by_admin_id', 'approved_at', 'rejected_at',
        'rejection_reason', 'requires_approval', 'approval_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'requires_approval' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }

    public function hosts(): HasMany
    {
        return $this->hasMany(ScanHost::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ScanService::class);
    }

    public function cveFindings(): HasMany
    {
        return $this->hasMany(CveFinding::class);
    }

    public function report(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ScanReport::class);
    }

    public function approvedByAdmin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'approved_by_admin_id');
    }

    public function summaryLabel(): string
    {
        return match ($this->type) {
            'cidr' => (string) $this->cidr,
            'range' => trim($this->start_ip.' - '.$this->end_ip),
            default => (string) $this->ip,
        };
    }
}
