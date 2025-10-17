<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriMenu extends Model
{
    /** @use HasFactory<\Database\Factories\KategoriMenuFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
    ];

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'kategori_id');
    }
}
