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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->string('gambar_url', 255)->nullable();
            $table->enum('status', ['Beberapa menu tidak halal', 'Aman/Halal'])->nullable();
            $table->foreignId('staff_id')->unique()->constrained('staffs');
            $table->boolean('is_active')->default(true);
            $table->enum('status_operasional', ['buka', 'tutup', 'istirahat', 'sibuk', 'tutup-permanent'])->default('tutup');
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
