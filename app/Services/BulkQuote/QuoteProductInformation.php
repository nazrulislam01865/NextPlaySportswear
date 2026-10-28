<?php

namespace App\Services\BulkQuote;

use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

/**
 * One source for the quote picker and its server-side selection checks.
 * The quote must always use current, published product configuration.
 */
final class QuoteProductInformation
{
    private const RELATIONS = [
        'images',
        'optionGroups.values',
        'sizeGroups.sizes',
        'attributeValues.attribute',
        'fabricPriceTables',
    ];

    public function find(int $id): ?Product
    {
        return Product::query()->published()->with(self::RELATIONS)->find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::query()->published()->with(self::RELATIONS)->where('slug', $slug)->first();
    }

    /** @return array<string,mixed> */
    public function describe(Product $product): array
    {
        $groups = $product->optionGroups->where('is_active', true)
            ->filter(fn ($group): bool => ($group->display_mode ?: 'customer') !== 'hidden' && $group->type !== 'file')
            ->map(function ($group): ?array {
                $values = $group->values->where('is_active', true)->map(fn ($value): array => [
                    'id' => (string) $value->id,
                    'label' => (string) $value->label,
                    'color' => $this->color($value->color_hex),
                    'default' => (bool) $value->is_default,
                ])->values()->all();

                $type = (string) $group->type;
                if (in_array($type, ['image', 'swatch', 'buttons', 'select', 'checkbox'], true) && $values === []) {
                    if ($group->display_mode === 'fixed' && filled($group->fixed_text_value)) {
                        $type = 'text';
                    } else {
                        return null;
                    }
                }
                $default = collect($values)->firstWhere('default', true);
                $fixed = $group->fixed_value_code;
                if ($fixed) {
                    $fixedOption = $group->values->firstWhere('code', $fixed);
                    $default = collect($values)->firstWhere('id', (string) $fixedOption?->id) ?? $default;
                }

                return [
                    'id' => (string) $group->id,
                    'label' => (string) $group->name,
                    'type' => $type,
                    'fixed' => $group->display_mode === 'fixed',
                    'placeholder' => (string) ($group->placeholder ?: ''),
                    // Product requirements are optional on a bulk quote; these are suggestions.
                    'default' => (string) ($default['id'] ?? ($group->fixed_text_value ?: '')),
                    'values' => $values,
                ];
            })->filter()->values()->all();

        // Some products specify their colors in the shared master attributes rather
        // than in a per-product option group. Do not duplicate an existing Color field.
        if (! collect($groups)->contains(fn (array $group): bool => preg_match('/colou?r/i', $group['label']) === 1)) {
            $colors = $product->attributeValues->filter(fn ($value): bool => (bool) $value->is_active
                && $value->attribute?->is_active
                && $value->attribute?->slug === 'color')
                ->map(fn ($value): array => [
                    'id' => 'a'.$value->id,
                    'label' => (string) $value->label,
                    'color' => $this->color($value->color_hex),
                    'default' => false,
                ])->values()->all();
            if ($colors !== []) {
                $groups[] = ['id' => 'attribute-colors', 'label' => 'Color', 'type' => 'swatch',
                    'placeholder' => '', 'default' => $colors[0]['id'], 'values' => $colors];
            }
        }

        // Products may define a fabric price table without an option group.
        if (! collect($groups)->contains(fn (array $group): bool => preg_match('/fabric|material/i', $group['label']) === 1)) {
            $fabrics = $product->fabricPriceTables->where('is_active', true)
                ->map(fn ($table): array => ['id' => 'f'.$table->id,
                    'label' => (string) ($table->fabric_label ?: $table->fabric_code ?: 'Fabric'),
                    'color' => null, 'default' => false])->values()->all();
            if ($fabrics !== []) {
                $groups[] = ['id' => 'fabric-tables', 'label' => 'Fabric', 'type' => 'select',
                    'placeholder' => '', 'default' => $fabrics[0]['id'], 'values' => $fabrics];
            }
        }

        $sizes = $product->sizeGroups->where('is_active', true)
            ->map(fn ($group): array => [
                'id' => (string) $group->id,
                'label' => (string) $group->name,
                'sizes' => $group->sizes->where('is_active', true)->map(fn ($size): array => [
                    'id' => (string) $size->id, 'label' => (string) $size->label,
                ])->values()->all(),
            ])->filter(fn (array $group): bool => $group['sizes'] !== [])->values()->all();

        return [
            'id' => $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'summary' => Str::limit(trim(strip_tags((string) ($product->short_description ?: ''))), 190),
            'image' => $product->primaryImageUrl(),
            'groups' => $groups,
            'size_groups' => $sizes,
        ];
    }

    /** @param array<string,mixed> $selection
     *  @return array<string,mixed>
     */
    public function validateAndSnapshot(array $selection): array
    {
        $product = $this->find((int) ($selection['product_id'] ?? 0));
        if (! $product) {
            throw ValidationException::withMessages([
                'product_information.product_id' => 'This product is no longer available. Please select another product.',
            ]);
        }
        $config = $this->describe($product);
        $submitted = (array) ($selection['options'] ?? []);
        $validOptions = [];

        foreach ($submitted as $groupId => $submittedValue) {
            $group = collect($config['groups'])->firstWhere('id', (string) $groupId);
            if (! $group) {
                $this->invalidSelection();
            }
            $type = $group['type'];
            if (($group['fixed'] ?? false) && $type !== 'checkbox' && (string) $submittedValue !== (string) $group['default']) {
                $this->invalidSelection();
            }
            if ($type === 'checkbox') {
                if (! is_array($submittedValue) || count($submittedValue) > 30) {
                    $this->invalidSelection();
                }
                $values = [];
                foreach ($submittedValue as $valueId) {
                    $option = collect($group['values'])->firstWhere('id', (string) $valueId);
                    if (! is_scalar($valueId) || ! $option) {
                        $this->invalidSelection();
                    }
                    $values[(string) $valueId] = $option['label'];
                }
                if ($values !== []) {
                    $validOptions[] = ['label' => $group['label'], 'values' => array_values($values)];
                }
            } elseif (in_array($type, ['image', 'swatch', 'buttons', 'select'], true)) {
                if (! is_scalar($submittedValue)) {
                    $this->invalidSelection();
                }
                $option = collect($group['values'])->firstWhere('id', (string) $submittedValue);
                if (! $option) {
                    $this->invalidSelection();
                }
                $validOptions[] = ['label' => $group['label'], 'value' => $option['label']];
            } else {
                if (! is_scalar($submittedValue) || mb_strlen((string) $submittedValue) > 500) {
                    $this->invalidSelection();
                }
                if (trim((string) $submittedValue) !== '' &&
                    (($type === 'number' && ! is_numeric((string) $submittedValue)) ||
                     ($type === 'date' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', (string) $submittedValue)))) {
                    $this->invalidSelection();
                }
                if (trim((string) $submittedValue) !== '') {
                    $validOptions[] = ['label' => $group['label'], 'value' => trim((string) $submittedValue)];
                }
            }
        }

        $qtyGroups = [];
        foreach ((array) ($selection['sizes'] ?? []) as $sizeGroupId => $sizeAmounts) {
            $group = collect($config['size_groups'])->firstWhere('id', (string) $sizeGroupId);
            if (! $group || ! is_array($sizeAmounts) || count($sizeAmounts) > 80) {
                $this->invalidSelection();
            }
            $selectedSizes = [];
            foreach ($sizeAmounts as $sizeId => $amount) {
                $size = collect($group['sizes'])->firstWhere('id', (string) $sizeId);
                if (! $size || ! is_scalar($amount) || ! preg_match('/^\d{1,5}$/D', (string) $amount) || (int) $amount > 99999) {
                    $this->invalidSelection();
                }
                if ((int) $amount > 0) {
                    $selectedSizes[] = ['size' => $size['label'], 'quantity' => (int) $amount];
                }
            }
            if ($selectedSizes !== []) {
                $qtyGroups[] = ['label' => $group['label'], 'sizes' => $selectedSizes];
            }
        }

        return [
            'product_id' => $product->id,
            'product_name' => $config['name'],
            'sku' => $config['sku'],
            'options' => $validOptions,
            'size_groups' => $qtyGroups,
            'notes' => trim((string) ($selection['notes'] ?? '')),
        ];
    }

    private function color(?string $hex): ?string
    {
        return preg_match('/^#?[a-f\d]{6}$/i', trim((string) $hex))
            ? '#'.strtoupper(ltrim(trim((string) $hex), '#')) : null;
    }

    private function invalidSelection(): never
    {
        throw ValidationException::withMessages([
            'product_information' => 'Some product options have changed. Please reselect the product and its options.',
        ]);
    }
}
