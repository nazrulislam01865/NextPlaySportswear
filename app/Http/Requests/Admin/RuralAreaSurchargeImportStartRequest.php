<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RuralAreaSurchargeImportStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'carrier' => strtoupper(trim((string) ($this->input('carrier') ?: 'UPS'))),
            'source_file' => trim((string) $this->input('source_file')),
            'replace_existing' => $this->boolean('replace_existing', true),
        ]);
    }

    public function rules(): array
    {
        return [
            'carrier' => ['required', 'string', 'max:40'],
            'source_file' => ['required', 'string', 'max:255'],
            'extra_charge' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'replace_existing' => ['required', 'boolean'],
            'expected_rows' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
