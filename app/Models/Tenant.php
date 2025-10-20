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

    // Define the table associated with the model
    protected $table = 'tenants';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'nama',
        'staff_id',
        'is_active',
        
    ];

    // Define relationships
    public function staff() : BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function menuItems() : HasMany
    {
        return $this->hasMany(MenuItem::class, 'tenant_id');
    }
}
