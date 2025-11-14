<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
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
            'gambar_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'status' => ['nullable', 'string', 'in:Beberapa menu tidak halal,Aman/Halal'],
            'staff_id' => ['sometimes', 'required', 'integer', 'exists:staffs,id', Rule::unique('tenants')->ignore($this->tenant)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
