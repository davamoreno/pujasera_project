<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class MetodePembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\MetodePembayaranFactory> */
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'metode_pembayarans';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'nama_metode',
        'tipe',
        'kode_unik',
        'logo_url',
        'is_aktif'
    ];

    // Define relationships
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'metode_pembayaran_id');
    }
}
