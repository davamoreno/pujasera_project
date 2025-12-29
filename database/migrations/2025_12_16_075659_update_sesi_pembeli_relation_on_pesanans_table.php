<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesanans', function (Blueprint $table) {
            // 1. Hapus constraint foreign key yang lama dulu
            // Laravel biasanya menamai constraint dengan format: namatabel_namakolom_foreign
            $table->dropForeign(['sesi_pembeli_id']);

            // 2. Ubah kolom agar bisa menerima nilai NULL (Wajib untuk nullOnDelete)
            $table->unsignedBigInteger('sesi_pembeli_id')->nullable()->change();

            // 3. Pasang constraint baru dengan aturan nullOnDelete
            $table->foreign('sesi_pembeli_id')
                  ->references('id')
                  ->on('sesi_pembelis')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanans', function (Blueprint $table) {
            // Kembalikan ke pengaturan awal jika di-rollback
            $table->dropForeign(['sesi_pembeli_id']);

            // Kembalikan jadi tidak boleh null (hati-hati jika sudah ada data null, ini bisa error)
            $table->unsignedBigInteger('sesi_pembeli_id')->nullable(false)->change();

            $table->foreign('sesi_pembeli_id')
                  ->references('id')
                  ->on('sesi_pembelis');
        });
    }
};