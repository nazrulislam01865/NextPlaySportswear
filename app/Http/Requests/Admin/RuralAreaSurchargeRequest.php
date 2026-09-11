<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RuralAreaSurchargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'legacy_mode' => $this->boolean('legacy_mode'),
            'name' => trim((string) $this->input('name')),
            'carrier' => strtoupper(trim((string) ($this->input('carrier') ?: 'UPS'))),
            'country' => trim((string) $this->input('country')),
            'iata_code' => strtoupper(trim((string) $this->input('iata_code'))),
            'state' => trim((string) $this->input('state')),
            'postal_code_patterns' => trim((string) $this->input('postal_code_patterns')),
            'postal_code_low' => trim((string) $this->input('postal_code_low')),
            'postal_code_high' => trim((string) ($this->input('postal_code_high') ?: $this->input('postal_code_low'))),
            'city' => trim((string) $this->input('city')),
            'origin_surcharge' => trim((string) $this->input('origin_surcharge')),
            'destination_surcharge' => trim((string) $this->input('destination_surcharge')),
            'extra_charge' => $this->input('extra_charge'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $legacy = $this->boolean('legacy_mode');

        return [
            'legacy_mode' => ['nullable', 'boolean'],
            'name' => [Rule::requiredIf($legacy), 'nullable', 'string', 'max:150'],
            'carrier' => [$legacy ? 'nullable' : 'required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
            'iata_code' => [$legacy ? 'nullable' : 'required', 'string', 'size:2', 'alpha'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code_patterns' => [Rule::requiredIf($legacy), 'nullable', 'string', 'max:4000'],
            'postal_code_low' => [$legacy ? 'nullable' : 'required', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'postal_code_high' => [$legacy ? 'nullable' : 'required', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'city' => ['nullable', 'string', 'max:160'],
            'origin_surcharge' => [$legacy ? 'nullable' : 'required', 'string', 'max:80'],
            'destination_surcharge' => [$legacy ? 'nullable' : 'required', 'string', 'max:80'],
            'extra_charge' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'iata_code.size' => 'The IATA code must contain exactly two letters.',
            'postal_code_low.regex' => 'Postal Code Low may contain only letters, numbers, spaces, and hyphens.',
            'postal_code_high.regex' => 'Postal Code High may contain only letters, numbers, spaces, and hyphens.',
            'postal_code_patterns.required' => 'Enter at least one ZIP/postal code, prefix pattern, or range.',
        ];
    }
}
