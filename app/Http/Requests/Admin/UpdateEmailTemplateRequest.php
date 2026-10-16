<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:100'],
            'preheader' => ['nullable', 'string', 'max:150'],
            'heading' => ['required', 'string', 'max:100'],
            'intro' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['required', 'string', 'max:50'],
            'cta_url' => ['required', 'string', 'max:100'],
            'cta_custom_url' => ['nullable', 'string', 'max:255'],
            'blocks' => ['nullable', 'array'],
            'action' => ['required', 'string', 'in:draft,publish'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'The email subject line is required.',
            'heading.required' => 'The email heading is required.',
            'cta_label.required' => 'The CTA button label is required.',
        ];
    }
}
