<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'question' => trim((string) $this->input('question')),
            'answer' => trim((string) $this->input('answer')),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Enter the FAQ question.',
            'answer.required' => 'Enter the FAQ answer.',
        ];
    }
}
