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

            // RELATION
            $table->foreignId('pesanan_id')->constrained('pesanans');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayarans');

            // AMOUNT
            $table->decimal('jumlah_bayar', 10, 2);
            $table->enum('status_pembayaran', ['pending', 'lunas', 'gagal'])->default('pending');
            $table->timestamp('waktu_bayar')->nullable();

            // XENDIT CORE FIELDS
            $table->string('reference_id')->unique(); // Safe
            $table->string('external_id')->index();
            $table->string('xendit_invoice_id')->nullable();
            $table->string('xendit_invoice_status')->nullable();
            $table->string('xendit_invoice_url')->nullable();
            $table->longText('xendit_qr_string')->nullable();
            $table->timestamp('xendit_expires_at')->nullable();
            $table->json('xendit_callback_payload')->nullable();

            // INTERNAL EXPIRY
            $table->timestamp('expires_at')->nullable();

            // PAYMENT RESULT
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);

            $table->timestamps();

            $table->index('reference_id'); 
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
