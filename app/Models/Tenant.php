<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
        'staff_id',
        'is_active',
        
    ];

    public function staff() : BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function menuItems() : HasMany
    {
        return $this->hasMany(MenuItem::class, 'tenant_id');
    }
}
