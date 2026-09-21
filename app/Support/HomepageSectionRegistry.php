<?php

namespace App\Support;

use App\Models\HomepageSection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class HomepageSectionRegistry
{
    private const RETIRED_KEYS = [
        'slider',
        'categories',
        'buyer_paths',
        'process',
        'featured_products',
        'latest_products',
        'best_selling_products',
        'best_selling_gear',
        'why_choose',
        'testimonials',
        'faq',
        'customization_options',
        'support',
        'final_cta',
        'popular_categories',
        'use_cases',
        'design_jersey',
        'bulk_order',
    ];

    /** @return array<int, string> */
    public static function retiredKeys(): array
    {
        return self::RETIRED_KEYS;
    }

    public static function isRetired(string $key): bool
    {
        return in_array($key, self::RETIRED_KEYS, true);
    }

    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        $sections = [
            [
                'key' => 'hero',
                'name' => 'Hero Banner',
                'component' => 'hero',
                'sort_order' => 10,
                'fields' => ['publishing'],
            ],
            [
                'key' => 'audience',
                'name' => 'Audience Tiles',
                'component' => 'audience',
                'sort_order' => 20,
                'items' => [
                    ['id' => 'men', 'title' => 'MEN', 'url' => '/men', 'image_alt' => 'Men sportswear'],
                    ['id' => 'women', 'title' => 'WOMEN', 'url' => '/products?q=women', 'image_alt' => 'Women sportswear'],
                    ['id' => 'kids', 'title' => 'KIDS', 'url' => '/products?q=kids', 'image_alt' => 'Kids sportswear'],
                ],
                'fields' => ['items', 'publishing'],
                'item_label' => 'Audience Tiles',
                'item_fields' => ['id', 'title', 'url', 'image_url', 'image_alt'],
            ],
            [
                'key' => 'shop_by_sport',
                'name' => 'Shop By Sport',
                'component' => 'shop_by_sport',
                'sort_order' => 30,
                'title' => 'SHOP BY SPORT',
                'settings' => [
                    'default_sport_id' => null,
                ],
                'item_button_defaults' => [
                    ['id' => 'button-jersey', 'label' => 'JERSEY', 'url' => '/products?q=jersey'],
                    ['id' => 'button-bottoms', 'label' => 'BOTTOMS', 'url' => '/products?q=bottoms'],
                    ['id' => 'button-uniform-kits', 'label' => 'UNIFORM KITS', 'url' => '/products?q=uniform'],
                    ['id' => 'button-accessories', 'label' => 'ACCESSORIES', 'url' => '/products?q=accessories'],
                ],
                'fields' => ['text', 'items', 'settings', 'publishing'],
                'item_label' => 'Sports',
                'item_fields' => ['id', 'category_id', 'title', 'url', 'image_url', 'image_alt', 'buttons'],
            ],
            [
                'key' => 'new_arrivals',
                'name' => 'New Arrivals',
                'component' => 'new_arrivals',
                'sort_order' => 40,
                'title' => 'NEW ARRIVALS',
                'fields' => ['text', 'publishing'],
            ],
            [
                'key' => 'shop_by_category',
                'name' => 'Shop By Category',
                'component' => 'shop_by_category',
                'sort_order' => 50,
                'title' => 'SHOP BY CATEGORY',
                'items' => [],
                'fields' => ['text', 'items', 'publishing'],
                'item_label' => 'Category Tiles',
                'item_fields' => ['id', 'category_id', 'title', 'url', 'image_url', 'image_alt'],
            ],
            [
                'key' => 'best_choices',
                'name' => 'Best Choices For You',
                'component' => 'best_choices',
                'sort_order' => 60,
                'title' => 'BEST CHOICES FOR YOU',
                'settings' => [
                    'tabs' => [
                        'featured' => ['label' => 'FEATURED', 'enabled' => true],
                        'popular' => ['label' => 'POPULAR', 'enabled' => true],
                        'trending' => ['label' => 'TRENDING', 'enabled' => true],
                    ],
                ],
                'fields' => ['text', 'settings', 'publishing'],
            ],
            [
                'key' => 'season_sale',
                'name' => 'Season Sale',
                'component' => 'season_sale',
                'sort_order' => 70,
                'title' => 'SEASON SALE',
                'description' => 'UP TO 20% OFF',
                'primary_label' => 'SHOP SALE',
                'primary_url' => '/products',
                'image_alt' => 'NextPlay season sale',
                'fields' => ['text', 'buttons', 'media', 'publishing'],
            ],
            [
                'key' => 'make_it_yours',
                'name' => 'Make It Yours',
                'component' => 'make_it_yours',
                'sort_order' => 80,
                'title' => 'MAKE IT YOURS',
                'primary_label' => 'Explore All',
                'primary_url' => '/products',
                'fields' => ['text', 'buttons', 'publishing'],
            ],
            [
                'key' => 'design_process',
                'name' => 'Design Process',
                'component' => 'design_process',
                'sort_order' => 90,
                'title' => 'HOW TO DESIGN A T-SHIRT USING NEXTPLAY',
                'items' => [
                    ['id' => 'choose-product', 'title' => 'Choose Product', 'description' => 'Pick the product, sport, category, or apparel type.'],
                    ['id' => 'share-details', 'title' => 'Share Custom Details', 'description' => 'Send your logo, colors, names, numbers, size list, and quantity.'],
                    ['id' => 'review-mockup', 'title' => 'Review Mockup', 'description' => 'We prepare or review the artwork before production.'],
                    ['id' => 'confirm-order', 'title' => 'Confirm Order', 'description' => 'Approve the final details, price, and timeline.'],
                    ['id' => 'production-shipping', 'title' => 'Production & Shipping', 'description' => 'Your order goes into production and ships to your address.'],
                ],
                'fields' => ['text', 'items', 'publishing'],
                'item_label' => 'Process Steps',
                'item_fields' => ['id', 'title', 'description', 'image_url', 'image_alt'],
            ],
        ];

        return collect($sections)->keyBy('key')->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function orderedDefinitions(): array
    {
        return collect(self::definitions())
            ->sortBy(fn (array $section): int => (int) ($section['sort_order'] ?? 0))
            ->values()
            ->all();
    }

    public static function definition(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }

    public static function ensureRows(?int $userId = null): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        foreach (self::orderedDefinitions() as $definition) {
            HomepageSection::query()->firstOrCreate(
                ['key' => $definition['key']],
                self::payloadForStorage($definition, $userId)
            );
        }
    }

    /** @return array<string, mixed> */
    public static function payloadForStorage(array $definition, ?int $userId = null): array
    {
        return [
            'name' => (string) $definition['name'],
            'eyebrow' => self::nullableString($definition['eyebrow'] ?? null),
            'title' => self::nullableString($definition['title'] ?? null),
            'description' => self::nullableString($definition['description'] ?? null),
            'primary_label' => self::nullableString($definition['primary_label'] ?? null),
            'primary_url' => self::nullableString($definition['primary_url'] ?? null),
            'secondary_label' => self::nullableString($definition['secondary_label'] ?? null),
            'secondary_url' => self::nullableString($definition['secondary_url'] ?? null),
            'image_path' => self::nullableString($definition['image_path'] ?? null),
            'image_url' => self::nullableString($definition['image_url'] ?? null),
            'image_alt' => self::nullableString($definition['image_alt'] ?? null),
            'mobile_image_path' => self::nullableString($definition['mobile_image_path'] ?? null),
            'mobile_image_url' => self::nullableString($definition['mobile_image_url'] ?? null),
            'mobile_image_alt' => self::nullableString($definition['mobile_image_alt'] ?? null),
            'hero_slides' => $definition['hero_slides'] ?? null,
            'items' => $definition['items'] ?? null,
            'settings' => $definition['settings'] ?? null,
            'is_active' => true,
            'sort_order' => (int) ($definition['sort_order'] ?? 0),
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    /** @return array<string, mixed> */
    public static function mergeForView(string $key, ?HomepageSection $section = null): array
    {
        $definition = self::definition($key) ?? [];
        $values = $section ? $section->toArray() : [];
        $merged = array_merge($definition, array_filter($values, static fn ($value): bool => $value !== null));

        foreach ([
            'eyebrow', 'title', 'description', 'primary_label', 'primary_url',
            'secondary_label', 'secondary_url', 'image_path', 'image_url', 'image_alt',
            'mobile_image_path', 'mobile_image_url', 'mobile_image_alt',
        ] as $nullableKey) {
            $merged[$nullableKey] = $merged[$nullableKey] ?? null;
        }

        $merged['key'] = $key;
        $merged['name'] = (string) ($merged['name'] ?? Str::of($key)->replace('_', ' ')->title());
        $merged['component'] = (string) ($definition['component'] ?? $key);
        $merged['fields'] = $definition['fields'] ?? ['text'];
        $merged['item_fields'] = $definition['item_fields'] ?? ['title', 'description'];
        $merged['item_label'] = $definition['item_label'] ?? 'Items';
        $merged['settings'] = self::mergeSettings(
            $key,
            (array) ($definition['settings'] ?? []),
            is_array($section?->settings) ? $section->settings : null,
        );
        $merged['items'] = self::mergeItems(
            is_array($definition['items'] ?? null) ? $definition['items'] : [],
            is_array($section?->items) ? $section->items : (is_array($merged['items'] ?? null) ? $merged['items'] : [])
        );

        if ($key === 'shop_by_sport') {
            $buttonDefaults = is_array($definition['item_button_defaults'] ?? null)
                ? $definition['item_button_defaults']
                : [];

            $merged['items'] = collect($merged['items'])
                ->map(function ($item) use ($buttonDefaults): mixed {
                    if (! is_array($item) || array_key_exists('buttons', $item)) {
                        return $item;
                    }

                    // Legacy sports that have never had per-sport buttons get
                    // the four starter buttons. An explicitly saved empty array
                    // is preserved so admins can delete every button if desired.
                    $item['buttons'] = $buttonDefaults;

                    return $item;
                })
                ->all();
        }

        $merged['is_active'] = (bool) ($merged['is_active'] ?? true);
        $merged['sort_order'] = (int) ($merged['sort_order'] ?? ($definition['sort_order'] ?? 0));
        $merged['image'] = self::publicImage($merged['image_path'] ?? null, $merged['image_url'] ?? null);
        $merged['mobile_image'] = self::publicImage($merged['mobile_image_path'] ?? null, $merged['mobile_image_url'] ?? null);
        $merged['mobile_image_alt'] = self::nullableString($merged['mobile_image_alt'] ?? null) ?: ($merged['image_alt'] ?? null);
        $merged['hero_slides'] = collect(is_array($merged['hero_slides'] ?? null) ? $merged['hero_slides'] : [])
            ->map(function ($slide, int $index): ?array {
                if (! is_array($slide)) {
                    return null;
                }

                $imagePath = self::nullableString($slide['image_path'] ?? null);
                $imageUrl = self::nullableString($slide['image_url'] ?? $slide['image'] ?? null);
                $image = self::publicImage($imagePath, $imageUrl);

                if ($image === null) {
                    return null;
                }

                return [
                    'id' => self::nullableString($slide['id'] ?? null) ?: 'hero-slide-'.($index + 1),
                    'image_path' => $imagePath,
                    'image_url' => $imageUrl,
                    'image' => $image,
                    'image_alt' => self::nullableString($slide['image_alt'] ?? $slide['alt'] ?? null) ?: 'Custom team sportswear',
                ];
            })
            ->filter()
            ->values()
            ->all();

        // This is admin/editor initialization metadata, not part of the
        // storefront section JSON contract.
        unset($merged['item_button_defaults']);

        return $merged;
    }

    /** @param array<int, array<string, mixed>> $defaults @param array<int, array<string, mixed>> $stored */
    private static function mergeItems(array $defaults, array $stored): array
    {
        $hasDefaultIds = collect($defaults)->contains(fn ($item): bool => is_array($item) && filled($item['id'] ?? null));

        if (! $hasDefaultIds) {
            return collect($stored)
                ->filter(fn ($item): bool => is_array($item))
                ->values()
                ->map(fn (array $item): array => self::withResolvedItemImage($item))
                ->all();
        }

        $storedById = collect($stored)
            ->filter(fn ($item): bool => is_array($item) && filled($item['id'] ?? null))
            ->keyBy(fn (array $item): string => (string) $item['id']);

        $merged = collect($defaults)->map(function ($default) use ($storedById): array {
            $default = is_array($default) ? $default : [];
            $id = (string) ($default['id'] ?? '');
            $stored = $id !== '' ? $storedById->get($id, []) : [];

            return self::withResolvedItemImage(array_merge($default, is_array($stored) ? $stored : []));
        });

        $defaultIds = collect($defaults)->pluck('id')->filter()->map(fn ($id): string => (string) $id);
        $extras = collect($stored)
            ->filter(fn ($item): bool => is_array($item))
            ->reject(fn (array $item): bool => filled($item['id'] ?? null) && $defaultIds->contains((string) $item['id']))
            ->map(fn (array $item): array => self::withResolvedItemImage($item));

        return $merged->concat($extras)->values()->all();
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private static function withResolvedItemImage(array $item): array
    {
        $item['image_path'] = self::nullableString($item['image_path'] ?? null);
        $item['image_url'] = self::nullableString($item['image_url'] ?? $item['image'] ?? null);
        $item['image_alt'] = self::nullableString($item['image_alt'] ?? null);
        $item['image'] = self::publicImage($item['image_path'], $item['image_url']);

        return $item;
    }

    /**
     * Merge section settings. Shop By Sport buttons are stored inside each
     * configured sport item. The retired global quick_links setting is stripped
     * from legacy rows so old default buttons cannot leak back into the UI.
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed>|null $stored
     * @return array<string, mixed>
     */
    private static function mergeSettings(string $key, array $defaults, ?array $stored): array
    {
        if ($stored === null) {
            return $defaults;
        }

        $merged = array_replace_recursive($defaults, $stored);

        if ($key === 'shop_by_sport') {
            unset($merged['quick_links']);
        }

        return $merged;
    }

    private static function publicImage(mixed $path, mixed $url): ?string
    {
        $path = trim((string) $path);
        if ($path !== '') {
            return PublicMedia::storedPathUrl($path);
        }

        $url = trim((string) $url);

        return $url !== '' ? PublicMedia::url(null, $url) : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
