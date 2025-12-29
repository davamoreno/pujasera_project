<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DetailPesanan;
use App\Models\Pembayaran;
use App\Models\SesiPembeli;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pesanan extends Model
{
    /** @use HasFactory<\Database\Factories\PesananFactory> */
    use HasFactory, SoftDeletes;

    // Define the table associated with the model
    protected $table = 'pesanans';
    
    // Define fillable attributes for mass assignment
    protected $fillable = [
        'sesi_pembeli_id',
        'kode_pesanan',
        'total_harga',
        'status_pesanan',
        'tenant_id',
        'nama_pembeli_snapshot',
    ];

    // Define relationships
    public function detailPesanans() : HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'pesanan_id');
    }

    public function pembayaran() : HasOne
    {
        return $this->hasOne(Pembayaran::class, 'pesanan_id');
    }

    public function tenant() : BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function sesiPembeli() : BelongsTo
    {
        return $this->belongsTo(SesiPembeli::class, 'sesi_pembeli_id');
    }

    public static function randomKodePesanan()
    {
        $randomString = strtoupper('ORD-' . \Illuminate\Support\Str::random(8));

        if (self::where('kode_pesanan', $randomString)->exists()) {
            return self::randomKodePesanan();
        }

        return $randomString;
    }
}
