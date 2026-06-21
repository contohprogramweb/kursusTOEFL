<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization akan dicek di controller menggunakan Gate
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
            // Tidak ada input tambahan yang diperlukan
        ];
    }
}
