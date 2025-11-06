<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Staff;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pemilikToko = Staff::where('username', 'pemiliktoko')->first();

        if ($pemilikToko) {
            Tenant::updateOrCreate(
                ['staff_id' => $pemilikToko->id], // Cari berdasarkan staff_id
                [
                    'nama' => 'Warung Budi',
                    'is_active' => true
                ]
            );
        }

        // Opsional: Buat 1 tenant lagi dengan staf acak
        $staffLain = Staff::factory()->create(['role_id' => 2]); // Buat 1 pemilik toko baru
        Tenant::factory()->create(['staff_id' => $staffLain->id, 'nama' => 'Kopi Senja']);
    }
}
