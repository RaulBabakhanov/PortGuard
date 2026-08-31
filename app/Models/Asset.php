<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    public const TYPES = [
        'web' => 'Web sitesi',
        'server' => 'Sunucu',
        'database' => 'Veritabanı',
        'kiosk' => 'Kamuya açık kiosk',
        'camera' => 'Kamera / IoT',
        'network' => 'Ağ cihazı',
        'other' => 'Diğer',
    ];

    public const CRITICALITIES = [
        'low' => 'Düşük',
        'medium' => 'Orta',
        'high' => 'Yüksek',
        'critical' => 'Kritik',
    ];

    protected $fillable = [
        'name', 'ip', 'asset_type', 'criticality', 'department_id',
        'owner_name', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->asset_type] ?? $this->asset_type;
    }

    public function criticalityLabel(): string
    {
        return self::CRITICALITIES[$this->criticality] ?? $this->criticality;
    }

    public function isCriticalTier(): bool
    {
        return in_array($this->criticality, ['high', 'critical'], true);
    }
}
