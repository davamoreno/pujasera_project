<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'roles';

    // Define fillable attributes for mass assignment
    protected $fillable = [
       'nama'
    ];

    // Define relationships
    public function staffs() : HasMany
    {
        return $this->hasMany(Staff::class, 'role_id');
    }
}
