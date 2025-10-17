<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\PembayaranFactory> */
    use HasFactory;

    protected $fillable = [
        'pesanan_id',
        'metode_pembayaran_id',
        'jumlah_bayar',
        'status_pembayaran',
        'waktu_pembayaran',
    ];

    public function pesanan() : BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
    public function metodePembayaran() : BelongsTo
    {
        return $this->belongsTo(MetodePembayaran::class, 'metode_pembayaran_id');
    }
}
