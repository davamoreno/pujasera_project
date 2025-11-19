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
                    'status' => 'Aman/Halal',
                    'gambar_url' => null,
                    'is_active' => true
                ],
            );
        }

        for ($i = 1; $i <= 20; $i++) {
            $staff = Staff::factory()->create(['role_id' => 2]); // Buat staf dengan role Pemilik Tenant

            Tenant::factory()->create([
                'staff_id' => $staff->id, 'nama' => 'Tenant ' . $i,
            ]);
        }
       
    }
}
