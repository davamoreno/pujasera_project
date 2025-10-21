<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'harga' => ['required', 'numeric', 'min:0'],
            'qty' => ['required', 'integer', 'min:0'],
            'kategori_id' => ['required', 'integer', 'exists:kategori_menu,id'],
            'gambar_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            
            // tenant_id wajib diisi HANYA JIKA user adalah Admin
            'tenant_id' => [
                Rule::requiredIf(fn () => Auth::user()->role->nama === 'Admin'),
                'integer',
                'exists:tenants,id'
            ],
        ];
    }
}
