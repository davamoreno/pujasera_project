<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    /** @use HasFactory<\Database\Factories\MenuItemFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'kategori_id',
        'nama',
        'deskripsi',
        'harga',
        'gambar_url',
        'is_tersedia',
    ];

    public function kategori() : BelongsTo
    {
        return $this->belongsTo(KategoriMenu::class, 'kategori_id');
    }

    public function tenant() : BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function detailPesanans() : HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'menu_item_id');
    }
}
