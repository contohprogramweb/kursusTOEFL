<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
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
        return [
            // Display Preferences
            'dark_mode' => ['nullable', 'boolean'],
            'font_size' => ['nullable', Rule::in(['small', 'medium', 'large', 'extra_large'])],
            'high_contrast' => ['nullable', 'boolean'],
            'animations' => ['nullable', 'boolean'],
            'screen_reader_opt' => ['nullable', 'boolean'],
            
            // Language Preference
            'language' => ['nullable', Rule::in(['id', 'en'])],
            
            // Do Not Disturb Settings
            'dnd_enabled' => ['nullable', 'boolean'],
            'dnd_start' => ['nullable', 'date_format:H:i'],
            'dnd_end' => ['nullable', 'date_format:H:i', 'after:dnd_start'],
            'dnd_days' => ['nullable', 'array'],
            'dnd_days.*' => ['integer', 'min:0', 'max:6'], // 0=Sunday, 6=Saturday
            
            // Notification Preferences (stored as JSON in a separate column or same table)
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.*' => ['nullable', 'array'],
            'notification_preferences.*.in_app' => ['nullable', 'boolean'],
            'notification_preferences.*.email' => ['nullable', 'boolean'],
            'notification_preferences.*.push' => ['nullable', 'boolean'],
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
            'font_size.in' => 'Ukuran font harus small, medium, large, atau extra_large.',
            'language.in' => 'Bahasa harus id atau en.',
            'dnd_start.date_format' => 'Format waktu DND harus HH:mm.',
            'dnd_end.date_format' => 'Format waktu DND harus HH:mm.',
            'dnd_end.after' => 'Waktu akhir DND harus setelah waktu mulai.',
            'dnd_days.*.min' => 'Hari DND tidak valid.',
            'dnd_days.*.max' => 'Hari DND tidak valid.',
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
            'dark_mode' => 'mode gelap',
            'font_size' => 'ukuran font',
            'high_contrast' => 'kontras tinggi',
            'animations' => 'animasi',
            'screen_reader_opt' => 'opsi pembaca layar',
            'language' => 'bahasa',
            'dnd_enabled' => 'jangan ganggu',
            'dnd_start' => 'waktu mulai DND',
            'dnd_end' => 'waktu akhir DND',
            'dnd_days' => 'hari DND',
            'notification_preferences' => 'preferensi notifikasi',
        ];
    }
}
