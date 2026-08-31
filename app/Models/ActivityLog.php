<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'description', 'properties', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            'auth.login' => 'Giriş yapıldı',
            'auth.logout' => 'Çıkış yapıldı',
            'scan.created' => 'Tarama oluşturuldu',
            'scan.started' => 'Tarama başlatıldı',
            'scan.engine' => 'Tarama motoru',
            'scan.completed' => 'Tarama tamamlandı',
            'scan.failed' => 'Tarama başarısız',
            'scan.deleted' => 'Tarama silindi',
            'target.created' => 'Hedef eklendi',
            'target.updated' => 'Hedef güncellendi',
            'target.deleted' => 'Hedef silindi',
            'schedule.created' => 'Zamanlama eklendi',
            'schedule.enabled' => 'Zamanlama açıldı',
            'schedule.disabled' => 'Zamanlama kapatıldı',
            'schedule.run_now' => 'Zamanlama çalıştırıldı',
            'schedule.deleted' => 'Zamanlama silindi',
            'settings.updated' => 'Ayarlar güncellendi',
            'profile.updated' => 'Profil güncellendi',
            'profile.deleted' => 'Hesap silindi',
            'notification.read' => 'Bildirim okundu',
            'notification.read_all' => 'Bildirimler okundu',
        ];
    }

    public function actionLabel(): string
    {
        return self::actionLabels()[$this->action] ?? $this->action;
    }

    public function ipLabel(): string
    {
        $ip = (string) ($this->ip_address ?? '');

        if ($ip === '::1') {
            return '127.0.0.1 (yerel)';
        }

        return $ip !== '' ? $ip : '—';
    }
}
