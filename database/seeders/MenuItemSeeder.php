<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Models\KategoriMenu;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantBudi = Tenant::where('nama', 'Warung Budi')->first();
        $katMakanan = KategoriMenu::where('nama', 'Makanan')->first();
        $katMinuman = KategoriMenu::where('nama', 'Minuman')->first();

        if ($tenantBudi && $katMakanan && $katMinuman) {
            // Buat Nasi Goreng
            MenuItem::updateOrCreate(
                ['nama' => 'Nasi Goreng Spesial', 'tenant_id' => $tenantBudi->id],
                [
                    'kategori_id' => $katMakanan->id,
                    'deskripsi' => 'Nasi goreng spesial pakai telur',
                    'harga' => 25000,
                    'qty' => 100, // Stok
                    'is_tersedia' => true
                ]
            );

            // Buat Es Teh
            MenuItem::updateOrCreate(
                ['nama' => 'Es Teh Manis', 'tenant_id' => $tenantBudi->id],
                [
                    'kategori_id' => $katMinuman->id,
                    'deskripsi' => 'Es teh manis segar',
                    'harga' => 5000,
                    'qty' => 200, // Stok
                    'is_tersedia' => true
                ]
            );
        }
    }
}
