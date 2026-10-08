<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HolidayCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'country_code' => ['required', 'string', 'max:10'],
            'country_name' => ['required', 'string', 'max:120'],
            'year' => ['required', 'integer', 'min:2020', 'max:2050'],
            'is_active' => ['nullable'],
            'description' => ['nullable', 'string', 'max:1000'],
            'dates' => ['nullable', 'array'],
            'dates.*.date' => ['required_with:dates', 'date_format:Y-m-d'],
            'dates.*.name' => ['required_with:dates', 'string', 'max:160'],
            'dates.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country')) {
            $country = (string) $this->input('country');
            if (str_contains($country, '|')) {
                [$code, $name] = explode('|', $country, 2);
                $this->merge([
                    'country_code' => trim($code),
                    'country_name' => trim($name),
                ]);
            }
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
