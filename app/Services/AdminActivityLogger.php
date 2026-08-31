<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminActivityLogger
{
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?AdminUser $admin = null,
        ?Request $request = null,
    ): AdminActivityLog {
        $request ??= request();
        $admin ??= Auth::guard('admin')->user();

        return AdminActivityLog::query()->create([
            'admin_user_id' => $admin?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
