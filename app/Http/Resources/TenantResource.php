<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'gambar_url' => $this->gambar_url 
                ? asset('storage/' . $this->gambar_url)
                : null,

            'status' => $this->status,
            'status_operasional' => $this->status_operasional,
            'is_active' => $this->is_active,

            'staff' => [
                'id' => $this->staff->id,
                'nama' => $this->staff->nama,
                'username' => $this->staff->username,
                'role' => $this->staff->role->nama,
            ],
            'menu_items' => MenuItemResource::collection($this->whenLoaded('menuItems')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
