<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'harga' => $this->harga,
            'qty' => $this->qty,
            'is_tersedia' => $this->is_tersedia,
            'status_kehalalan' => $this->status_kehalalan,
            'gambar_url' => $this->gambar_url ? url('storage/' . $this->gambar_url) : null,
            'kategori' => $this->kategori,
            'tenant' => $this->whenLoaded('tenant', function() {
                return $this->tenant->nama;
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
