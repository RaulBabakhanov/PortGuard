<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::query()->updateOrCreate(
            ['email' => 'admin@portguard.com.tr'],
            [
                'name' => 'Süper Admin',
                'password' => 'PortGuard!2026',
                'is_active' => true,
            ]
        );
    }
}
