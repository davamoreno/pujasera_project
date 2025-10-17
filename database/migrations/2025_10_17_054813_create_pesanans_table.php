<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_pembeli_id')->constrained('sesi_pembelis');
            $table->string('kode_pesanan')->unique();
            $table->decimal('total_harga', 10, 2);
            $table->enum('status_pesanan', ['pending', 'diproses', 'selesai', 'dibatalkan'])
                  ->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};
