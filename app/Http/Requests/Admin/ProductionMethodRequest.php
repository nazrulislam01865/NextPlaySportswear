<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductionMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductionMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $code = trim((string) ($this->input('code') ?: Str::slug($name)));

        $this->merge([
            'name' => $name,
            'code' => Str::slug($code),
            'description' => trim((string) $this->input('description')),
            'minimum_days' => $this->input('minimum_days'),
            'maximum_days' => $this->input('maximum_days'),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }

    public function rules(): array
    {
        $productionMethod = $this->route('production_method') ?? $this->route('productionMethod');
        $id = is_object($productionMethod) ? $productionMethod->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('production_methods', 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'minimum_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'maximum_days' => ['required', 'integer', 'gte:minimum_days', 'max:3650'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Use lowercase letters, numbers, and hyphens only, for example standard-production.',
            'maximum_days.gte' => 'Maximum working days must be greater than or equal to minimum working days.',
        ];
    }
}
