<?php

namespace App\Services\Catalog;

use App\Models\CatalogAttribute;
use App\Models\CatalogAttributeValue;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductOptionFilterSyncService
{
    /** @var list<string> */
    private const SUPPORTED_TYPES = ['image', 'swatch', 'buttons', 'select', 'checkbox'];

    /**
     * Convert filter-enabled product options into reusable catalog attributes,
     * assign the matching values to the product, and expose the attributes on
     * every category where the product is currently placed.
     *
     * @param  array<int, int|string>  $manualAttributeValueIds
     */
    public function sync(Product $product, array $manualAttributeValueIds = []): void
    {
        $manualIds = collect($manualAttributeValueIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $generatedValueIds = collect();
        $categoryIds = $product->categories()->pluck('categories.id')->map(fn ($id): int => (int) $id);

        $groups = $product->optionGroups()
            ->with(['values' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->where('is_active', true)
            ->where('use_as_filter', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($groups as $group) {
            if (! $this->isSupported($group)) {
                $group->update(['use_as_filter' => false, 'catalog_attribute_id' => null]);
                continue;
            }

            $attribute = $this->upsertAttribute($group);
            $group->update(['catalog_attribute_id' => $attribute->id]);

            $optionValues = $this->filterValuesForAssignment($group);
            foreach ($optionValues as $optionValue) {
                $attributeValue = $this->upsertAttributeValue($attribute, $optionValue);
                $generatedValueIds->push($attributeValue->id);
            }

            $this->attachAttributeToCategories($attribute, $categoryIds);
        }

        $product->attributeValues()->sync(
            $manualIds->merge($generatedValueIds)->unique()->values()->all()
        );
    }

    private function isSupported(ProductOptionGroup $group): bool
    {
        return $group->display_mode !== 'hidden'
            && in_array($group->type, self::SUPPORTED_TYPES, true);
    }

    private function upsertAttribute(ProductOptionGroup $group): CatalogAttribute
    {
        $slug = Str::slug($group->code ?: $group->name);
        $attribute = CatalogAttribute::withTrashed()->firstOrNew(['slug' => $slug]);

        if ($attribute->trashed()) {
            $attribute->restore();
        }

        $attribute->fill([
            'name' => trim($group->name),
            'display_type' => $this->displayType($group->type),
            'is_filterable' => true,
            'is_searchable' => true,
            'is_active' => true,
            'sort_order' => $attribute->exists ? $attribute->sort_order : $group->sort_order,
            'created_by' => $attribute->created_by ?: auth()->id(),
            'updated_by' => auth()->id(),
        ])->save();

        return $attribute;
    }

    private function upsertAttributeValue(
        CatalogAttribute $attribute,
        ProductOptionValue $optionValue
    ): CatalogAttributeValue {
        $slug = Str::slug($optionValue->code ?: $optionValue->label);

        return CatalogAttributeValue::query()->updateOrCreate(
            ['attribute_id' => $attribute->id, 'slug' => $slug],
            [
                'label' => trim($optionValue->label),
                'color_hex' => $optionValue->color_hex,
                'image_path' => $optionValue->image_path,
                'image_url' => $optionValue->image_url,
                'is_active' => true,
                'sort_order' => $optionValue->sort_order,
            ]
        );
    }

    /** @return Collection<int, ProductOptionValue> */
    private function filterValuesForAssignment(ProductOptionGroup $group): Collection
    {
        $values = $group->values->where('is_active', true)->values();

        if ($group->display_mode === 'fixed') {
            if ($group->type === 'checkbox') {
                $defaults = $values->where('is_default', true)->values();

                return $defaults->isNotEmpty() ? $defaults : $values->take(1)->values();
            }

            $fixedCode = trim((string) $group->fixed_value_code);
            $fixed = $values->firstWhere('code', $fixedCode)
                ?? $values->firstWhere('is_default', true)
                ?? $values->first();

            return $fixed ? collect([$fixed]) : collect();
        }

        return $values;
    }

    /** @param Collection<int, int> $categoryIds */
    private function attachAttributeToCategories(CatalogAttribute $attribute, Collection $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            DB::table('category_filters')->insertOrIgnore([
                'category_id' => $categoryId,
                'attribute_id' => $attribute->id,
                'label' => $attribute->name,
                'is_expanded' => true,
                'sort_order' => $attribute->sort_order,
            ]);
        }
    }

    private function displayType(string $type): string
    {
        return match ($type) {
            'swatch' => 'color',
            'image' => 'image',
            'select' => 'select',
            'buttons' => 'radio',
            default => 'checkbox',
        };
    }
}
