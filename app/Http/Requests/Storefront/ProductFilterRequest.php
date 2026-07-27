<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:100'],
            'categories' => ['nullable', 'array', 'max:50'],
            'categories.*' => ['integer', 'distinct', 'exists:categories,id'],
            'sports' => ['nullable', 'array', 'max:30'],
            'sports.*' => ['integer', 'distinct', 'exists:categories,id'],
            'product_types' => ['nullable', 'array', 'max:50'],
            'product_types.*' => ['string', 'max:120', 'distinct'],
            'colors' => ['nullable', 'array', 'max:50'],
            'colors.*' => ['string', 'max:160', 'distinct', 'regex:/^[a-z0-9][a-z0-9._-]*$/i'],
            'materials' => ['nullable', 'array', 'max:50'],
            'materials.*' => ['string', 'max:160', 'distinct', 'regex:/^[a-z0-9][a-z0-9._-]*$/i'],
            'artwork_methods' => ['nullable', 'array', 'max:50'],
            'artwork_methods.*' => ['string', 'max:160', 'distinct', 'regex:/^[a-z0-9][a-z0-9._-]*$/i'],
            'attributes' => ['nullable', 'array', 'max:30'],
            'attributes.*' => ['array', 'max:100'],
            'attributes.*.*' => ['string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'moq' => ['nullable', 'array', 'max:6'],
            'moq.*' => ['string', 'distinct', Rule::in(['single', '2-5', '6-11', '12-24', '25-49', '50-plus'])],
            'customization' => ['nullable', 'array', 'max:4'],
            'customization.*' => ['string', 'distinct', Rule::in(['customizable', 'ready-made', 'artwork-upload', 'player-details'])],
            'availability' => ['nullable', 'array', 'max:3'],
            'availability.*' => ['string', 'distinct', Rule::in(['in-stock', 'backorder', 'made-to-order'])],
            'min_rating' => ['nullable', 'integer', Rule::in([2, 3, 4])],
            'sort' => ['nullable', Rule::in([
                'featured', 'best-selling', 'newest', 'price-low', 'price-high', 'rating-high', 'name-asc',
            ])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('min_price') && $this->filled('max_price')
                && (float) $this->input('max_price') < (float) $this->input('min_price')) {
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
            'tag' => trim((string) ($validated['tag'] ?? '')),
            'categories' => $this->integerArray($validated['categories'] ?? []),
            'subcategory' => [],
            'sports' => $this->integerArray($validated['sports'] ?? []),
            'product_types' => $this->stringArray($validated['product_types'] ?? []),
            'colors' => $this->stringArray($validated['colors'] ?? []),
            'materials' => $this->stringArray($validated['materials'] ?? []),
            'artwork_methods' => $this->stringArray($validated['artwork_methods'] ?? []),
            'attributes' => collect($validated['attributes'] ?? [])
                ->map(fn ($values) => $this->stringArray($values))
                ->filter()
                ->all(),
            'min_price' => isset($validated['min_price']) ? (float) $validated['min_price'] : null,
            'max_price' => isset($validated['max_price']) ? (float) $validated['max_price'] : null,
            'moq' => $this->stringArray($validated['moq'] ?? []),
            'customization' => $this->stringArray($validated['customization'] ?? []),
            'availability' => $this->stringArray($validated['availability'] ?? []),
            'min_rating' => isset($validated['min_rating']) ? (int) $validated['min_rating'] : null,
            'sort' => (string) ($validated['sort'] ?? 'featured'),
        ];
    }

    /** @return array<int, int> */
    private function integerArray(array $values): array
    {
        return collect($values)
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function stringArray(array $values): array
    {
        return collect($values)
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
