<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SesiPembeli extends Model
{
    /** @use HasFactory<\Database\Factories\SesiPembeliFactory> */
    use HasFactory;

    protected $fillable = [
        'kode_transaksi',
        'nama',
        'waktu_mulai',
    ];

    public function pesanan() : HasMany
    {
        return $this->hasMany(Pesanan::class, 'sesi_pembeli_id');
    }

}
