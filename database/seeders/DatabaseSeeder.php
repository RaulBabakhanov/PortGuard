<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@portguard.com.tr'],
            [
                'name' => 'PortGuard Panel',
                'password' => Hash::make('PortGuard!2026'),
                'email_verified_at' => now(),
            ]
        );

        $this->call(AdminUserSeeder::class);
        $this->call(MunicipalitySeeder::class);
    }
}
