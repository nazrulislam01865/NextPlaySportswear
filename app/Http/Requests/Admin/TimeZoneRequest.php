<?php

namespace App\Http\Requests\Admin;

use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimeZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => trim((string) $this->input('label', '')),
            'identifier' => trim((string) $this->input('identifier', '')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $timeZone = $this->route('time_zone') ?? $this->route('timeZone');

        return [
            'label' => ['required', 'string', 'max:160'],
            'identifier' => [
                'required',
                'string',
                'max:100',
                Rule::in(DateTimeZone::listIdentifiers()),
                Rule::unique('time_zones', 'identifier')->ignore($timeZone?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
