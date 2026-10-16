<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailGlobalBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'header_bg_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i'],
            'button_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i'],
            'font_family' => ['nullable', 'string', 'max:50'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'support_email' => ['required', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'logo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:2048',
            ],
            'remove_logo' => ['nullable', 'boolean'],
            'action' => ['nullable', 'string', 'in:draft,publish'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'header_bg_color.regex' => 'The header background color must be a valid hex color code (e.g., #0B2A4A).',
            'button_color.regex' => 'The button color must be a valid hex color code (e.g., #F15A2B).',
            'support_email.required' => 'A valid support email address is required.',
            'logo.max' => 'The brand logo must not exceed 2MB.',
        ];
    }
}
