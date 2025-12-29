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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('kategori_id')->constrained('kategori_menus');
            $table->string('nama', 255);
            $table->text('deskripsi')->nullable();
            $table->enum('status_kehalalan', ['Halal', 'Tidak Halal'])->nullable();
            $table->decimal('harga', 10, 2);
            $table->string('gambar_url', 255)->nullable();
            $table->boolean('is_tersedia')->default(true);
            $table->integer('qty')->default(0);
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
