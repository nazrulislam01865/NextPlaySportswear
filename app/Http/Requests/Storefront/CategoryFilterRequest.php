<?php

namespace App\Http\Requests\Storefront;

class CategoryFilterRequest extends ProductFilterRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'subcategory' => ['nullable', 'array', 'max:50'],
            'subcategory.*' => ['integer', 'distinct', 'exists:categories,id'],
            'categories' => ['prohibited'],
            'tag' => ['prohibited'],
        ]);
    }

    public function filters(): array
    {
        $filters = parent::filters();
        $validated = $this->validated();

        $filters['categories'] = [];
        $filters['tag'] = '';
        $filters['subcategory'] = collect($validated['subcategory'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        return $filters;
    }
}
