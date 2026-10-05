<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CountryCallingCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_name' => trim((string) $this->input('country_name', '')),
            'iso_code' => strtoupper(trim((string) $this->input('iso_code', ''))),
            'dial_code' => trim((string) $this->input('dial_code', '')),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }

    public function rules(): array
    {
        $countryCallingCode = $this->route('countryCallingCode');
        $id = is_object($countryCallingCode) ? $countryCallingCode->getKey() : null;

        return [
            'country_name' => ['required', 'string', 'max:120'],
            'iso_code' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/', Rule::unique('country_calling_codes', 'iso_code')->ignore($id)],
            'dial_code' => ['required', 'string', 'max:12', 'regex:/^\+[0-9]{1,10}$/'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'iso_code.regex' => 'Use a two-letter country code, for example US, GB, or BD.',
            'dial_code.regex' => 'Use an international calling code beginning with +, for example +1 or +880.',
        ];
    }
}
