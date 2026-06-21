<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'target_score' => ['nullable', 'integer', 'min:0', 'max:120'],
            'test_date' => ['nullable', 'date', 'after:today'],
            'learning_preference' => ['nullable', Rule::in(['visual', 'auditory', 'kinesthetic'])],
            'timezone' => ['nullable', 'timezone'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+62[0-9]{9,13}$/',
                Rule::unique('user_profiles', 'phone')->ignore($userId, 'user_id'),
            ],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
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
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.min' => 'Nama lengkap minimal 2 karakter.',
            'target_score.max' => 'Target score maksimal 120.',
            'test_date.after' => 'Tanggal tes harus di masa depan.',
            'learning_preference.in' => 'Preferensi belajar harus visual, auditory, atau kinesthetic.',
            'phone.regex' => 'Nomor telepon harus dimulai dengan +62 diikuti 9-13 angka.',
            'avatar.max' => 'Ukuran avatar maksimal 2MB.',
            'avatar.dimensions' => 'Dimensi avatar tidak valid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'bio' => 'bio',
            'target_score' => 'target score',
            'test_date' => 'tanggal tes',
            'learning_preference' => 'preferensi belajar',
            'timezone' => 'zona waktu',
            'phone' => 'nomor telepon',
            'avatar' => 'foto profil',
        ];
    }
}
