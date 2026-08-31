<?php

namespace Database\Seeders;

use App\Models\AllowedNetwork;
use App\Models\Asset;
use App\Models\Department;
use App\Models\MunicipalitySetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        MunicipalitySetting::current();

        $bilgi = Department::query()->updateOrCreate(
            ['code' => 'BILGI'],
            ['name' => 'Bilgi İşlem', 'notes' => 'Merkezi BT birimi', 'is_active' => true]
        );
        $mali = Department::query()->updateOrCreate(
            ['code' => 'MALI'],
            ['name' => 'Mali Hizmetler', 'notes' => null, 'is_active' => true]
        );

        foreach ([
            ['name' => 'Yerel ağ', 'cidr' => '192.168.0.0/16'],
            ['name' => 'Özel ağ 10.x', 'cidr' => '10.0.0.0/8'],
            ['name' => 'Özel ağ 172.16', 'cidr' => '172.16.0.0/12'],
            ['name' => 'Localhost', 'cidr' => '127.0.0.0/8'],
        ] as $row) {
            AllowedNetwork::query()->updateOrCreate(
                ['cidr' => $row['cidr']],
                ['name' => $row['name'], 'department_id' => $bilgi->id, 'is_active' => true]
            );
        }

        Asset::query()->updateOrCreate(
            ['ip' => '127.0.0.1'],
            [
                'name' => 'Yerel test sunucusu',
                'asset_type' => 'server',
                'criticality' => 'medium',
                'department_id' => $bilgi->id,
                'owner_name' => 'Bilgi İşlem',
                'is_active' => true,
            ]
        );

        Asset::query()->updateOrCreate(
            ['ip' => '192.168.1.1'],
            [
                'name' => 'Belediye ağ geçidi',
                'asset_type' => 'network',
                'criticality' => 'high',
                'department_id' => $bilgi->id,
                'is_active' => true,
            ]
        );

        User::query()->where('email', 'admin@portguard.com.tr')->update([
            'department_id' => $bilgi->id,
        ]);

        // Mali birim için örnek varlık
        Asset::query()->updateOrCreate(
            ['ip' => '10.0.0.50'],
            [
                'name' => 'Muhasebe sunucusu',
                'asset_type' => 'database',
                'criticality' => 'critical',
                'department_id' => $mali->id,
                'owner_name' => 'Mali Hizmetler',
                'is_active' => true,
            ]
        );
    }
}
