<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MetodePembayaran;

class MetodePembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MetodePembayaran::updateOrCreate(
            ['kode_unik' => 'TUNAI'],
            [
                'nama_metode' => 'Tunai',
                'tipe' => 'TUNAI',
                'is_aktif' => true
            ]
        );

        MetodePembayaran::updateOrCreate(
            ['kode_unik' => 'QRIS'],
            [
                'nama_metode' => 'QRIS (Konfirmasi Manual)',
                'tipe' => 'QRIS',
                'is_aktif' => true
            ]
        );
        
        MetodePembayaran::updateOrCreate(
            ['kode_unik' => 'GOPAY'],
            [
                'nama_metode' => 'Gopay (Otomatis)',
                'tipe' => 'E-WALLET',
                'is_aktif' => true
            ]
        );
    }
}