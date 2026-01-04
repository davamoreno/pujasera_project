<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\PembayaranFactory> */
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'pembayarans';

    // Define appended attributes
    protected $appends = ['checkout_link'];

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'pesanan_id',
        'reference_id',
        'metode_pembayaran_id',
        'jumlah_bayar',
        'status_pembayaran',
        'xendit_expires_at',
        'expires_at',
        'waktu_bayar',
        'external_id',
    ];


    // Define accessor for checkout_link
    public function checkoutLink() : Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                 if(!empty($attributes['xendit_invoice_url'])){
                    return $attributes['xendit_invoice_url'];
                 }
                 return null;
                }
        );
    }

    // Define relationships
    public function pesanan() : BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    public function metodePembayaran() : BelongsTo
    {
        return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id');
    }
    
    public function tenant() : BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
