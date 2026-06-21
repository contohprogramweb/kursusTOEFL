<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitInviteCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya parent yang bisa submit invite code
        return $this->user()->isParent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invite_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[A-Z0-9]+$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invite_code.required' => 'Kode undangan wajib diisi.',
            'invite_code.size' => 'Kode undangan harus 6 karakter.',
            'invite_code.regex' => 'Kode undangan hanya boleh berisi huruf besar dan angka.',
        ];
    }
}
