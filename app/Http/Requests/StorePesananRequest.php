<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePesananRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan membuat request ini.
     * Karena ini endpoint publik, kita set true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Info Sesi/Pelanggan
            'nama_pelanggan' => ['required', 'string', 'max:255'],
            'kode_sesi' => ['required', 'string', 'max:100'], // Bisa jadi nomor meja

            // Info Pembayaran
            'metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],

            // Validasi Array Item
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}