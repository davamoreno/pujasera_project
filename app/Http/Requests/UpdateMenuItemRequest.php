<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
             'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'harga' => ['sometimes', 'required', 'numeric', 'min:0'],
            'qty' => ['sometimes', 'required', 'integer', 'min:0'],
            'kategori_id' => ['sometimes', 'required', 'integer', 'exists:kategori_menu,id'],
            'gambar_url' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'is_tersedia' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
