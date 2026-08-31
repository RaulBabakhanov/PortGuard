<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Target extends Model
{
    protected $fillable = [
        'user_id', 'name', 'type', 'ip', 'cidr', 'start_ip', 'end_ip', 'ports', 'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function label(): string
    {
        return match ($this->type) {
            'cidr' => $this->cidr,
            'range' => $this->start_ip.' - '.$this->end_ip,
            default => $this->ip,
        } ?? $this->name;
    }
}
