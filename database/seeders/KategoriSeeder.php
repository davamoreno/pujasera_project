<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KategoriMenu;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KategoriMenu::create(['nama'=>'makanan']);
        KategoriMenu::create(['nama'=>'minuman']);
        KategoriMenu::create(['nama'=>'cemilan']);
    }
}
