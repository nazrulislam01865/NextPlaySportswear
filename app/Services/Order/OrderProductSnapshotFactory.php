<?php

namespace App\Services\Order;

use App\Models\Product;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Builds the immutable product snapshot stored on each NextPlay order item.
 *
 * The snapshot intentionally contains both raw database attributes/relations
 * and the resolved storefront representation used by the configurator. This
 * means an old order remains self-contained even after the live product,
 * options, sizes, images, prices or fulfillment settings are edited later.
 */
final class OrderProductSnapshotFactory
{
    public const SCHEMA_VERSION = 1;

    public function __construct(private readonly ProductCatalogService $catalog)
    {
    }

    /**
     * @param array<string,mixed> $customization
     * @param array<string,mixed> $orderContext
     * @return array<string,mixed>
     */
    public function make(
        Product $product,
        array $customization,
        array $orderContext = [],
        string $captureReason = 'checkout',
    ): array {
        $product->loadMissing($this->snapshotRelations());
        $storefront = $this->catalog->fromModel($product);
        $resolved = $this->resolveCustomization($storefront, $customization);

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'captured_at' => now()->toIso8601String(),
            'capture_reason' => $captureReason,
            'product' => [
                'attributes' => $product->attributesToArray(),
                'storefront' => $storefront,
                'relations' => $this->relationSnapshot($product),
            ],
            'ordered_customization' => $customization,
            'selected_configuration' => $resolved,
            'order_context' => $orderContext,
        ];
    }

    /**
     * Preserve the cart-side product payload even if the live product row can no
     * longer be resolved (for example during a legacy/backfill edge case).
     *
     * @param array<string,mixed> $productData
     * @param array<string,mixed> $customization
     * @param array<string,mixed> $orderContext
     * @return array<string,mixed>
     */
    public function makeFallback(array $productData, array $customization, array $orderContext = []): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'captured_at' => now()->toIso8601String(),
            'capture_reason' => 'checkout_product_row_unavailable',
            'product' => [
                'attributes' => [],
                'storefront' => $productData,
                'relations' => [],
            ],
            'ordered_customization' => $customization,
            'selected_configuration' => [
                'submitted_configuration' => (array) ($customization['configuration'] ?? []),
                'options' => [],
                'sizes' => (array) ($customization['size_breakdown'] ?? []),
                'roster' => [
                    'enabled' => (bool) data_get($customization, 'configuration.roster_enabled', false),
                    'fields' => (array) ($customization['roster_fields'] ?? []),
                    'rows' => (array) data_get($customization, 'configuration.roster', []),
                ],
                'artwork' => [
                    'status' => $customization['artwork_status'] ?? null,
                    'settings' => [],
                    'files' => (array) ($customization['artwork_files'] ?? []),
                ],
                'production_speed' => null,
                'shipping_method' => null,
                'sample' => (array) ($customization['sample'] ?? []),
                'fulfillment' => (array) ($customization['fulfillment'] ?? []),
                'notes' => $customization['notes'] ?? null,
                'size_summary' => $customization['size_summary'] ?? null,
                'design_option' => $customization['design_option'] ?? null,
                'delivery_preference' => $customization['delivery_preference'] ?? null,
            ],
            'order_context' => $orderContext,
        ];
    }

    /** @return array<int,string> */
    private function snapshotRelations(): array
    {
        return [
            'category',
            'subcategory',
            'categories',
            'attributeValues.attribute',
            'images.mediaLibraryImage',
            'optionGroups.catalogAttribute',
            'optionGroups.values.jerseyCustomizationOption',
            'optionGroups.values.worldCupCustomizationOption',
            'sizeGroups.masterGroup',
            'sizeGroups.sizes',
            'priceTiers',
            'fabricPriceTables.jerseyCustomizationOption',
            'fabricPriceTables.tiers',
            'artworkMethods',
            'productionSpeeds.productionMethod',
            'shippingMethods.shippingMethod',
            'faqs',
        ];
    }

    /** @return array<string,mixed> */
    private function relationSnapshot(Product $product): array
    {
        return [
            'category' => $this->modelSnapshot($product->category),
            'subcategory' => $this->modelSnapshot($product->subcategory),
            'categories' => $product->categories->map(fn (Model $category): array => $this->modelSnapshot($category))->values()->all(),
            'attribute_values' => $product->attributeValues->map(fn (Model $value): array => array_merge(
                $this->modelSnapshot($value),
                ['attribute' => $this->modelSnapshot($value->attribute)],
            ))->values()->all(),
            'images' => $product->images->map(fn (Model $image): array => array_merge(
                $this->modelSnapshot($image),
                [
                    'public_url' => method_exists($image, 'publicUrl') ? $image->publicUrl() : null,
                    'media_library_image' => $this->modelSnapshot($image->mediaLibraryImage),
                ],
            ))->values()->all(),
            'option_groups' => $product->optionGroups->map(fn (Model $group): array => array_merge(
                $this->modelSnapshot($group),
                [
                    'catalog_attribute' => $this->modelSnapshot($group->catalogAttribute),
                    'values' => $group->values->map(fn (Model $value): array => array_merge(
                        $this->modelSnapshot($value),
                        [
                            'public_images' => method_exists($value, 'publicImages') ? $value->publicImages() : [],
                            'jersey_customization_option' => $this->modelSnapshot($value->jerseyCustomizationOption),
                            'world_cup_customization_option' => $this->modelSnapshot($value->worldCupCustomizationOption),
                        ],
                    ))->values()->all(),
                ],
            ))->values()->all(),
            'size_groups' => $product->sizeGroups->map(fn (Model $group): array => array_merge(
                $this->modelSnapshot($group),
                [
                    'chart_image_url' => method_exists($group, 'chartImageUrl') ? $group->chartImageUrl() : null,
                    'master_group' => $this->modelSnapshot($group->masterGroup),
                    'sizes' => $group->sizes->map(fn (Model $size): array => $this->modelSnapshot($size))->values()->all(),
                ],
            ))->values()->all(),
            'price_tiers' => $product->priceTiers->map(fn (Model $tier): array => $this->modelSnapshot($tier))->values()->all(),
            'fabric_price_tables' => $product->fabricPriceTables->map(fn (Model $table): array => array_merge(
                $this->modelSnapshot($table),
                [
                    'jersey_customization_option' => $this->modelSnapshot($table->jerseyCustomizationOption),
                    'tiers' => $table->tiers->map(fn (Model $tier): array => $this->modelSnapshot($tier))->values()->all(),
                ],
            ))->values()->all(),
            'artwork_methods' => $product->artworkMethods->map(fn (Model $method): array => $this->modelSnapshot($method))->values()->all(),
            'production_speeds' => $product->productionSpeeds->map(fn (Model $speed): array => array_merge(
                $this->modelSnapshot($speed),
                ['production_method' => $this->modelSnapshot($speed->productionMethod)],
            ))->values()->all(),
            'shipping_methods' => $product->shippingMethods->map(fn (Model $method): array => array_merge(
                $this->modelSnapshot($method),
                ['shipping_method' => $this->modelSnapshot($method->shippingMethod)],
            ))->values()->all(),
            'faqs' => $product->faqs->map(fn (Model $faq): array => $this->modelSnapshot($faq))->values()->all(),
        ];
    }

    /** @return array<string,mixed>|null */
    private function modelSnapshot(?Model $model): ?array
    {
        if (! $model) {
            return null;
        }

        $data = $model->attributesToArray();
        if ($model->relationLoaded('pivot') && $model->pivot instanceof Model) {
            $data['_pivot'] = $model->pivot->attributesToArray();
        }

        return $data;
    }

    /**
     * Resolve all submitted option/size/fulfillment identifiers against the
     * product definition that existed when checkout completed.
     *
     * @param array<string,mixed> $product
     * @param array<string,mixed> $customization
     * @return array<string,mixed>
     */
    private function resolveCustomization(array $product, array $customization): array
    {
        $configuration = (array) ($customization['configuration'] ?? []);
        $selections = (array) ($configuration['selections'] ?? []);
        $multiSelections = (array) ($configuration['multi_selections'] ?? []);
        $inputs = (array) ($configuration['inputs'] ?? []);
        $quantities = (array) ($configuration['quantities'] ?? []);

        $resolvedOptions = collect((array) ($product['option_groups'] ?? []))
            ->map(function (array $group) use ($selections, $multiSelections, $inputs): ?array {
                $groupKey = (string) ($group['id'] ?? '');
                if ($groupKey === '') {
                    return null;
                }

                $selectedCodes = collect(Arr::wrap($selections[$groupKey] ?? []))
                    ->merge(Arr::wrap($multiSelections[$groupKey] ?? []))
                    ->filter(fn ($value): bool => is_scalar($value) && trim((string) $value) !== '')
                    ->map(fn ($value): string => (string) $value)
                    ->unique()
                    ->values();

                if ($selectedCodes->isEmpty() && filled($group['fixed_value_code'] ?? null)) {
                    $selectedCodes->push((string) $group['fixed_value_code']);
                }

                $selectedValues = collect((array) ($group['values'] ?? []))
                    ->filter(fn (array $value): bool => $selectedCodes->contains((string) ($value['id'] ?? '')))
                    ->values()
                    ->all();

                $inputValue = $inputs[$groupKey] ?? null;
                if ($selectedValues === [] && blank($inputValue) && blank($group['fixed_text_value'] ?? null)) {
                    return null;
                }

                return [
                    'group_id' => $groupKey,
                    'group_label' => (string) ($group['label'] ?? Str::headline($groupKey)),
                    'section' => $group['section'] ?? null,
                    'type' => $group['type'] ?? null,
                    'display_mode' => $group['display_mode'] ?? null,
                    'required' => (bool) ($group['required'] ?? false),
                    'selected_codes' => $selectedCodes->all(),
                    'selected_values' => $selectedValues,
                    'input_value' => $inputValue ?? ($group['fixed_text_value'] ?? null),
                    'definition' => $group,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $resolvedSizes = collect((array) ($product['size_groups'] ?? []))
            ->flatMap(function (array $group) use ($quantities): array {
                $groupId = (string) ($group['id'] ?? '');

                return collect((array) ($group['sizes'] ?? []))
                    ->map(function (array $size) use ($group, $groupId, $quantities): ?array {
                        $sizeCode = (string) ($size['code'] ?? '');
                        $key = $groupId.':'.$sizeCode;
                        $quantity = max(0, (int) ($quantities[$key] ?? 0));
                        if ($quantity < 1) {
                            return null;
                        }

                        return [
                            'quantity_key' => $key,
                            'quantity' => $quantity,
                            'group_id' => $groupId,
                            'group_label' => $group['label'] ?? null,
                            'group_definition' => $group,
                            'size_code' => $sizeCode,
                            'size_label' => $size['label'] ?? $sizeCode,
                            'size_definition' => $size,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            })
            ->values()
            ->all();

        $productionCode = (string) ($configuration['production_speed'] ?? '');
        $shippingCode = (string) ($configuration['shipping_method'] ?? '');

        return [
            'submitted_configuration' => $configuration,
            'options' => $resolvedOptions,
            'sizes' => $resolvedSizes,
            'roster' => [
                'enabled' => (bool) ($configuration['roster_enabled'] ?? false),
                'fields' => (array) ($customization['roster_fields'] ?? data_get($product, 'roster.fields', [])),
                'rows' => (array) ($configuration['roster'] ?? []),
            ],
            'artwork' => [
                'status' => $customization['artwork_status'] ?? null,
                'settings' => (array) ($product['artwork_upload'] ?? []),
                'files' => (array) ($customization['artwork_files'] ?? []),
            ],
            'production_speed' => collect((array) ($product['production_speeds'] ?? []))
                ->firstWhere('id', $productionCode),
            'shipping_method' => collect((array) ($product['shipping_methods'] ?? []))
                ->firstWhere('id', $shippingCode),
            'sample' => (array) ($customization['sample'] ?? []),
            'fulfillment' => (array) ($customization['fulfillment'] ?? []),
            'notes' => $customization['notes'] ?? null,
            'size_summary' => $customization['size_summary'] ?? null,
            'design_option' => $customization['design_option'] ?? null,
            'delivery_preference' => $customization['delivery_preference'] ?? null,
        ];
    }
}
