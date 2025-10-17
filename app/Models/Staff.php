<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    /** @use HasFactory<\Database\Factories\StaffFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role_id'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'staff_id');
    }
}
