<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        // Model nesnesini cache'leme — unserialize __PHP_Incomplete_Class hatası verir.
        $id = Cache::remember('municipality.settings.id', 3600, function () {
            return static::query()->firstOrCreate([], [
                'kvkk_retention_days' => 365,
                'approval_host_threshold' => 16,
                'enforce_allowed_networks' => true,
                'require_approval_for_critical' => true,
            ])->id;
        });

        return static::query()->findOrFail($id);
    }

    public static function flushCache(): void
    {
        Cache::forget('municipality.settings');
        Cache::forget('municipality.settings.id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
    }
}
