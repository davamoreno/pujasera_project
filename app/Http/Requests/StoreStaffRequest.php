<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreStaffRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:255', 'unique:staffs,username'],
            'password' => ['required', 'string', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
            'gambar_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
