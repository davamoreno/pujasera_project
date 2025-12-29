<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    /** @use HasFactory<\Database\Factories\MenuItemFactory> */
    use HasFactory, SoftDeletes;

    // Define the table associated with the model
    protected $table = 'menu_items';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'tenant_id',
        'kategori_id',
        'nama',
        'deskripsi',
        'harga',
        'qty',
        'gambar_url',
        'is_tersedia',
        'status_kehalalan',  
    ];

    // Define relationships
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
