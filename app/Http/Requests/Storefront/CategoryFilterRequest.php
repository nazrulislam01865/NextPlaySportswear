<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryFilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'array', 'max:50'],
            'subcategory.*' => ['integer', 'distinct', 'exists:categories,id'],
            'attributes' => ['nullable', 'array', 'max:30'],
            'attributes.*' => ['array', 'max:100'],
            'attributes.*.*' => ['string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'in_stock' => ['nullable', 'boolean'],
            'customizable' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['featured', 'price-low', 'price-high', 'name-asc', 'newest'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('min_price') && $this->filled('max_price') && (float) $this->input('max_price') < (float) $this->input('min_price')) {
                $validator->errors()->add('max_price', 'The maximum price must be greater than or equal to the minimum price.');
            }
            foreach (array_keys((array) $this->input('attributes', [])) as $slug) {
                if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $slug)) {
                    $validator->errors()->add('attributes', 'An invalid attribute filter was supplied.');
                }
            }
        });
    }

    public function filters(): array
    {
        $validated = $this->validated();
        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'subcategory' => collect($validated['subcategory'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'attributes' => collect($validated['attributes'] ?? [])->map(fn ($values) => collect($values)->map(fn ($value) => (string) $value)->unique()->values()->all())->filter()->all(),
            'min_price' => isset($validated['min_price']) ? (float) $validated['min_price'] : null,
            'max_price' => isset($validated['max_price']) ? (float) $validated['max_price'] : null,
            'in_stock' => filter_var($validated['in_stock'] ?? false, FILTER_VALIDATE_BOOL),
            'customizable' => filter_var($validated['customizable'] ?? false, FILTER_VALIDATE_BOOL),
            'sort' => (string) ($validated['sort'] ?? 'featured'),
        ];
    }
}
