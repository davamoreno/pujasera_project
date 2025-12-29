<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Pesanan;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Builder;

class SesiPembeli extends Model
{
    /** @use HasFactory<\Database\Factories\SesiPembeliFactory> 
     *  @use SoftDeletes
    */
    use HasFactory, SoftDeletes, MassPrunable;

    // Define the table associated with the model
    protected $table = 'sesi_pembelis';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'kode_sesi',
        'nama',
        'is_closed',
        'expired_at',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'expired_at' => 'datetime',
    ];

    // Define the prunable query
    public function prunable() : Builder
    {
        return static::where('expired_at', '<', now()->subDay());
    }

    // Define relationships
    public function pesanan() : HasMany
    {
        return $this->hasMany(Pesanan::class, 'sesi_pembeli_id');
    }

}
