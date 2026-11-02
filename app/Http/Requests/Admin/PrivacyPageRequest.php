<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PrivacyPageRequest extends FormRequest
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
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.subtitle' => ['required', 'string', 'max:255'],
            'hero.description' => ['required', 'string', 'max:1000'],

            'sections' => ['required', 'array'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.content' => ['required', 'string', 'max:3000'],

            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

