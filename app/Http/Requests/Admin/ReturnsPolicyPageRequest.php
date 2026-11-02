<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReturnsPolicyPageRequest extends FormRequest
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
            'hero.description' => ['required', 'string', 'max:1000'],

            'sections.01.title' => ['required', 'string', 'max:255'],
            'sections.01.content' => ['required', 'string', 'max:1000'],

            'sections.02.title' => ['required', 'string', 'max:255'],
            'sections.02.content' => ['required', 'string', 'max:1000'],

            'sections.03.title' => ['required', 'string', 'max:255'],
            'sections.03.content' => ['required', 'string', 'max:1000'],

            'sections.04.title' => ['required', 'string', 'max:255'],
            'sections.04.content' => ['required', 'string', 'max:1000'],

            'sections.05.title' => ['required', 'string', 'max:255'],
            'sections.05.items' => ['required', 'array'],
            'sections.05.items.*' => ['required', 'string', 'max:500'],

            'sections.06.title' => ['required', 'string', 'max:255'],
            'sections.06.content' => ['required', 'string', 'max:1000'],

            'sections.07.title' => ['required', 'string', 'max:255'],
            'sections.07.content' => ['required', 'string', 'max:1000'],

            'sections.08.title' => ['required', 'string', 'max:255'],
            'sections.08.content' => ['required', 'string', 'max:1000'],
            'sections.08.btn_text' => ['required', 'string', 'max:120'],
            'sections.08.btn_url' => ['required', 'string', 'max:255'],

            'disclaimer' => ['required', 'string', 'max:1000'],

            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

