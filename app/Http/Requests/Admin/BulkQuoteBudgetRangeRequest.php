<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BulkQuoteBudgetRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $label = trim((string) $this->input('label', ''));
        $value = trim((string) ($this->input('value') ?: Str::slug($label)));

        $this->merge([
            'label' => $label,
            'value' => Str::slug($value),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }

    public function rules(): array
    {
        $budgetRange = $this->route('bulkQuoteBudgetRange');
        $id = is_object($budgetRange) ? $budgetRange->getKey() : null;

        return [
            'label' => ['required', 'string', 'max:120'],
            'value' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('bulk_quote_budget_ranges', 'value')->ignore($id)],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.regex' => 'Use lowercase letters, numbers, and hyphens only.',
        ];
    }
}
