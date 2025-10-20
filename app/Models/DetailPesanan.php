<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPesanan extends Model
{
    /** @use HasFactory<\Database\Factories\DetailPesananFactory> */
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'detail_pesanans';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'pesanan_id',
        'menu_item_id',
        'jumlah',
        'harga_saat_pesan',
        'catatan',
    ];

    // Define relationships
    public function menuItem() : BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function pesanan() : BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
}
