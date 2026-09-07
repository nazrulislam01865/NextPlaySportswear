<?php

namespace App\Support;

use App\Models\Product;

class ProductRoster
{
    public const DEFAULT_TITLE = 'Add item details';

    /**
     * Roster fields are intentionally product-agnostic.
     *
     * The capability method is retained as the single gate for older callers.
     * Every product profile can now opt into roster fields through its product
     * settings, so profile type no longer controls availability.
     */
    public static function supports(?string $productProfile = null): bool
    {
        return true;
    }

    /** @return array<int, array{key:string,label:string,type:string,max_length:int,required:bool,enabled:bool}> */
    public static function defaultFields(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'max_length' => 60, 'required' => false, 'enabled' => true],
            ['key' => 'number', 'label' => 'Number', 'type' => 'number', 'max_length' => 12, 'required' => false, 'enabled' => true],
        ];
    }

    /**
     * Build the generic storefront/cart roster contract from the persisted
     * product model. The database column names remain legacy-compatible so
     * existing installations do not need a destructive rename migration.
     *
     * @return array{enabled:bool,optional:bool,title:string,fields:array<int, array<string, mixed>>}
     */
    public static function forProduct(Product $product): array
    {
        return [
            'enabled' => (bool) $product->jersey_roster_enabled,
            'optional' => (bool) $product->jersey_roster_optional,
            'title' => filled($product->jersey_roster_title)
                ? (string) $product->jersey_roster_title
                : self::DEFAULT_TITLE,
            'fields' => collect($product->jersey_roster_fields ?? self::defaultFields())
                ->filter(fn ($field): bool => is_array($field) && (bool) ($field['enabled'] ?? true))
                ->values()
                ->all(),
        ];
    }

    /**
     * Resolve a product-array roster configuration while supporting the legacy
     * `jersey_roster` payload during the transition to the generic `roster`
     * name. New code should publish/read `roster`; old fixtures and cached
     * payloads keep working through the fallback.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public static function settings(array $product): array
    {
        $settings = $product['roster'] ?? $product['jersey_roster'] ?? [];

        return is_array($settings) ? $settings : [];
    }
}
