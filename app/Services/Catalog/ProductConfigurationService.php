<?php

namespace App\Services\Catalog;

use App\Services\Cart\CartService;
use App\Services\Storefront\ProductCatalogService;

final class ProductConfigurationService
{
    public function __construct(
        private readonly ProductCatalogService $products,
        private readonly CartService $cart,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function configuration(string $slug): ?array
    {
        $product = $this->products->findFullBySlug($slug);
        if (! $product) {
            return null;
        }

        return [
            'schema_version' => 1,
            'product' => [
                'id' => isset($product['id']) ? (int) $product['id'] : null,
                'slug' => (string) ($product['slug'] ?? ''),
                'name' => (string) ($product['title'] ?? ''),
                'sku' => (string) ($product['sku'] ?? ''),
                'profile' => (string) ($product['product_profile'] ?? 'standard'),
                'currency' => (string) ($product['currency'] ?? 'USD'),
                'customizable' => (bool) ($product['is_customizable'] ?? false),
            ],
            'quantity' => [
                'minimum' => max(1, (int) ($product['minimum_quantity'] ?? 1)),
                'maximum' => isset($product['maximum_quantity']) && $product['maximum_quantity'] !== null
                    ? (int) $product['maximum_quantity']
                    : 999,
                'track_inventory' => (bool) ($product['track_inventory'] ?? false),
                'stock_quantity' => isset($product['stock_quantity']) ? (int) $product['stock_quantity'] : null,
                'allow_backorder' => (bool) ($product['allow_backorder'] ?? false),
            ],
            'option_groups' => collect((array) ($product['option_groups'] ?? []))
                ->map(fn (array $group): array => $this->optionGroup($group))
                ->values()
                ->all(),
            'size_groups' => collect((array) ($product['size_groups'] ?? []))
                ->map(fn (array $group): array => $this->sizeGroup($group))
                ->values()
                ->all(),
            'roster' => (array) ($product['roster'] ?? $product['jersey_roster'] ?? [
                'enabled' => false,
                'optional' => true,
                'fields' => [],
            ]),
            'artwork' => array_merge(
                (array) ($product['artwork_upload'] ?? ['enabled' => false, 'required' => false]),
                ['methods' => array_values((array) ($product['artwork_methods'] ?? []))]
            ),
            'sample' => (array) ($product['sample'] ?? ['available' => false, 'charge' => 0]),
            'fulfillment' => [
                'production_methods_enabled' => (bool) ($product['production_methods_enabled'] ?? false),
                'production_speeds' => array_values((array) ($product['production_speeds'] ?? [])),
                'shipping_methods_enabled' => (bool) ($product['shipping_methods_enabled'] ?? false),
                'shipping_methods' => array_values((array) ($product['shipping_methods'] ?? [])),
            ],
            'pricing' => [
                'base_price' => round((float) ($product['base_price'] ?? 0), 2),
                'currency' => (string) ($product['currency'] ?? 'USD'),
                'tiers' => array_values((array) ($product['price_tiers'] ?? [])),
                'table' => (array) ($product['price_table'] ?? []),
                'fabric_tables' => array_values((array) ($product['fabric_price_tables'] ?? [])),
            ],
            'request_schema' => [
                'selections' => 'object<string,string>',
                'multi_selections' => 'object<string,string[]>',
                'inputs' => 'object<string,string>',
                'quantities' => 'object<"group:size",integer>',
                'production_speed' => 'string|null',
                'shipping_method' => 'string|null',
                'roster_enabled' => 'boolean',
                'roster' => 'array<{values:object<string,string>}>',
                'sample_requested' => 'boolean',
            ],
        ];
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>|null
     */
    public function preview(string $slug, array $payload): ?array
    {
        $configuration = $this->configuration($slug);
        if ($configuration === null) {
            return null;
        }

        $payload['product_slug'] = $slug;
        // Explicitly discard any fields a browser might try to use to influence
        // authoritative pricing. CartService recalculates all pricing fields.
        foreach (['unit_price', 'customization_unit_price', 'shipping_unit_price', 'line_subtotal', 'customization_total', 'product_shipping_total', 'line_total', 'total'] as $field) {
            unset($payload[$field]);
        }

        return [
            'product' => $configuration['product'],
            'item' => $this->cart->previewItem($payload),
        ];
    }

    /** @param array<string, mixed> $group
     *  @return array<string, mixed>
     */
    private function optionGroup(array $group): array
    {
        return [
            'id' => (string) ($group['id'] ?? ''),
            'label' => (string) ($group['label'] ?? ''),
            'description' => $group['description'] ?? null,
            'placeholder' => $group['placeholder'] ?? null,
            'section' => $group['section'] ?? null,
            'type' => (string) ($group['type'] ?? 'select'),
            'master_type' => $group['master_type'] ?? $group['jersey_customization_type'] ?? null,
            'display_mode' => (string) ($group['display_mode'] ?? 'customer'),
            'fixed_value_code' => $group['fixed_value_code'] ?? null,
            'fixed_text_value' => $group['fixed_text_value'] ?? null,
            'show_in_summary' => (bool) ($group['show_in_summary'] ?? true),
            'required' => (bool) ($group['required'] ?? false),
            'minimum_selections' => isset($group['minimum_selections']) ? (int) $group['minimum_selections'] : null,
            'maximum_selections' => isset($group['maximum_selections']) ? (int) $group['maximum_selections'] : null,
            'accepted_file_types' => $this->acceptedFileTypes($group['accepted_file_types'] ?? null),
            'maximum_file_size_mb' => isset($group['maximum_file_size_mb']) ? (int) $group['maximum_file_size_mb'] : null,
            'values' => collect((array) ($group['values'] ?? []))->map(fn (array $value): array => [
                'id' => (string) ($value['id'] ?? ''),
                'label' => (string) ($value['label'] ?? ''),
                'description' => $value['description'] ?? null,
                'color' => $value['color'] ?? null,
                'contrast' => $value['contrast'] ?? null,
                'image' => $value['image'] ?? null,
                'images' => array_values((array) ($value['images'] ?? [])),
                'price_delta' => round((float) ($value['price_delta'] ?? 0), 4),
                'charge_type' => (string) ($value['charge_type'] ?? 'per_unit'),
                'stock_quantity' => isset($value['stock_quantity']) ? (int) $value['stock_quantity'] : null,
                'default' => (bool) ($value['default'] ?? false),
                'fabric_price_table' => is_array($value['fabric_price_table'] ?? null) ? $value['fabric_price_table'] : null,
            ])->values()->all(),
        ];
    }

    /** @return array<int, string> */
    private function acceptedFileTypes(mixed $value): array
    {
        $types = is_array($value)
            ? $value
            : (preg_split('/[\s,;|]+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return collect($types)
            ->map(fn ($type): string => strtolower(ltrim(trim((string) $type), '.')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $group
     *  @return array<string, mixed>
     */
    private function sizeGroup(array $group): array
    {
        return [
            'id' => (string) ($group['id'] ?? ''),
            'label' => (string) ($group['label'] ?? ''),
            'sizes' => collect((array) ($group['sizes'] ?? []))->map(fn (array $size): array => [
                'code' => (string) ($size['code'] ?? ''),
                'label' => (string) ($size['label'] ?? ''),
                'price_delta' => round((float) ($size['price_delta'] ?? 0), 4),
            ])->values()->all(),
            'chart' => (array) ($group['chart'] ?? ['enabled' => false]),
        ];
    }
}
