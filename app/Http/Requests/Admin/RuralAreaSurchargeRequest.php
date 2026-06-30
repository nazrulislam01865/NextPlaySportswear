<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RuralAreaSurchargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'country' => trim((string) ($this->input('country') ?: 'United States')),
            'state' => trim((string) $this->input('state')),
            'postal_code_patterns' => trim((string) $this->input('postal_code_patterns')),
            'amount' => $this->input('amount'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'country' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code_patterns' => ['required', 'string', 'max:4000'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code_patterns.required' => 'Enter at least one ZIP/postal code, prefix pattern, or range.',
            'amount.min' => 'The surcharge amount must be greater than zero.',
        ];
    }
}
