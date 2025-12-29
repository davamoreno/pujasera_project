<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Staff;
use App\Models\MenuItem;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory, SoftDeletes;

    // Define the table associated with the model
    protected $table = 'tenants';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'nama',
        'gambar_url',
        'status',
        'staff_id',
        'status_operasional',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_operasional' => TenantStatus::class,
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

    public function bisaMenerimaPesanan()
    {
        return $this->is_active && $this->status_operasional === TenantStatus::OPEN;
    }

    public function pembayarans() : HasManyThrough
    {
        return $this->hasManyThrough(Pembayaran::class, Pesanan::class, 'tenant_id', 'pesanan_id');
    }

    public function pesanans() : HasMany
    {
        return $this->hasMany(Pesanan::class, 'tenant_id');
    }
}
