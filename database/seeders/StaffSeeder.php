<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Staff;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan role sudah ada
        $adminRole = Role::where('nama', 'Admin')->first();
        $pemilikTenantRole = Role::where('nama', 'Pemilik Tenant')->first();

        // 2. Buat satu Admin spesifik untuk login
        Staff::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
            ]
        );

        // 3. Buat satu Pemilik Toko spesifik untuk login
        Staff::updateOrCreate(
            ['username' => 'pemiliktoko'],
            [
                'nama' => 'Budi Tenant',
                'password' => Hash::make('password'),
                'role_id' => $pemilikTenantRole->id,
            ]
        );

        // 4. Buat 8 staf acak lainnya
       Staff::factory(25)->create();
    }
}
