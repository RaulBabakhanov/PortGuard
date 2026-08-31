<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class MunicipalitySetting extends Model
{
    protected $fillable = [
        'kvkk_retention_days',
        'approval_host_threshold',
        'enforce_allowed_networks',
        'require_approval_for_critical',
    ];

    protected function casts(): array
    {
        return [
            'enforce_allowed_networks' => 'boolean',
            'require_approval_for_critical' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return Cache::remember('municipality.settings', 3600, function () {
            return static::query()->firstOrCreate([], [
                'kvkk_retention_days' => 365,
                'approval_host_threshold' => 16,
                'enforce_allowed_networks' => true,
                'require_approval_for_critical' => true,
            ]);
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('municipality.settings');
        Cache::forget('audit.expiring_reports');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
    }
}
