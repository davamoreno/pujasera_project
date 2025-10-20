<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriMenu extends Model
{
    /** @use HasFactory<\Database\Factories\KategoriMenuFactory> */
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'kategori_menus';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'nama',
    ];

    // Define relationships
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'kategori_id');
    }
}
