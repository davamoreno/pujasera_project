<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::create([
            'nama' => 'Warung Abhi',
            'staff_id' => 1,
            'is_active' => true,
        ]);

        Tenant::create([
            'nama' => 'Warung Dava',
            'staff_id' => 2,
            'is_active' => true,
        ]);
    }
}
