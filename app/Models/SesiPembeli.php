<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SesiPembeli extends Model
{
    /** @use HasFactory<\Database\Factories\SesiPembeliFactory> */
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'sesi_pembelis';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'kode_sesi',
        'nama',
        'waktu_mulai',
    ];

    // Define relationships
    public function pesanan() : HasMany
    {
        return $this->hasMany(Pesanan::class, 'sesi_pembeli_id');
    }

}
