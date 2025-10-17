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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->unique()->constrained('pesanans');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayarans');
            $table->decimal('jumlah_bayar', 10, 2);
            $table->enum('status_pembayaran', ['pending', 'lunas', 'gagal'])->default('pending');
            $table->timestamp('waktu_bayar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
