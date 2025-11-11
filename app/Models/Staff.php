<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Staff extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\StaffFactory> */
    use HasFactory, HasApiTokens;

    // Define the table associated with the model
    protected $table = 'staffs';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'nama',
        'username',
        'password',
        'role_id'
    ];

    // Define hidden attributes for arrays
    protected $hidden = [
        'password',
    ];

    // Define relationships
    public function role() : BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function tenant() : HasOne
    {
        return $this->hasOne(Tenant::class, 'staff_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
