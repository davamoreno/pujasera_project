<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
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
            // Username harus unik, TAPI abaikan username milik staff yang sedang di-update.
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('staffs')->ignore($this->staff)],
            // Password bersifat opsional. Hanya divalidasi jika ada di request.
            'password' => ['sometimes', 'required', 'string', Password::min(8)],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
        ];
    }
}
