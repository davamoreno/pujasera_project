<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pesanan extends Model
{
    /** @use HasFactory<\Database\Factories\PesananFactory> */
    use HasFactory;

    protected $fillable = [
        'sesi_pembelian_id',
        'kode_pesanan',
        'total_harga',
        'status_pesanan',
    ];

    public function detailPesanans() : HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'pesanan_id');
    }

    public function pembayaran() : HasOne
    {
        return $this->hasOne(Pembayaran::class, 'pesanan_id');
    }

    public function sesiPembelian() : BelongsTo
    {
        return $this->belongsTo(SesiPembeli::class, 'sesi_pembelian_id');
    }
}
