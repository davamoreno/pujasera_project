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
        Schema::create('staffs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->string('username', 255)->unique();
            $table->string('password');
            $table->foreignId('role_id')->constrained('roles');
            $table->string('gambar_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};
