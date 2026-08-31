<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanReport extends Model
{
    protected $fillable = [
        'scan_id',
        'download_token',
        'filename',
        'mime_type',
        'byte_size',
        'content_sha256',
        'content_hmac',
        'storage_path',
        'content_encrypted',
        'created_by_admin_id',
    ];

    protected $hidden = [
        'content_encrypted',
        'content_hmac',
        'storage_path',
        'download_token',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_id');
    }

    /** Liste sorgularında PDF blob'unu DB'den çekme. */
    public function scopeListMeta(Builder $query): Builder
    {
        return $query->select([
            'id',
            'scan_id',
            'filename',
            'byte_size',
            'content_sha256',
            'created_at',
            'updated_at',
        ]);
    }

    public function shortHash(): string
    {
        return substr($this->content_sha256, 0, 12);
    }
}
