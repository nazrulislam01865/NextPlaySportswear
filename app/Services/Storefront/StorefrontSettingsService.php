<?php

namespace App\Services\Storefront;

use App\Models\StorefrontSetting;
use App\Support\PublicMedia;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class StorefrontSettingsService
{
    public const HEADER_KEY = 'header';
    public const NAVIGATION_KEY = 'navigation';
    public const FOOTER_KEY = 'footer';

    private const CACHE_SECONDS = 3600;

    /** @return array<string, mixed> */
    public function header(): array
    {
        $settings = $this->get(self::HEADER_KEY);

        if (trim((string) data_get($settings, 'branding.logo')) === '') {
            data_set($settings, 'branding.logo', (string) config('storefront.vue_logo'));
        }

        if (trim((string) data_get($settings, 'branding.logo_alt')) === '') {
            data_set($settings, 'branding.logo_alt', (string) config('storefront.name', 'NextPlay Sportswear'));
        }

        return $settings;
    }

    /** @return array<string, mixed> */
    public function navigation(): array
    {
        return $this->get(self::NAVIGATION_KEY);
    }

    /** @return array<string, mixed> */
    public function footer(): array
    {
        return $this->get(self::FOOTER_KEY);
    }

    /** @param array<string, mixed> $settings */
    public function updateHeader(array $settings, ?int $updatedBy = null): StorefrontSetting
    {
        return $this->update(self::HEADER_KEY, $settings, $updatedBy);
    }

    /** @param array<string, mixed> $settings */
    public function updateNavigation(array $settings, ?int $updatedBy = null): StorefrontSetting
    {
        return $this->update(self::NAVIGATION_KEY, $settings, $updatedBy);
    }

    /** @param array<string, mixed> $settings */
    public function updateFooter(array $settings, ?int $updatedBy = null): StorefrontSetting
    {
        return $this->update(self::FOOTER_KEY, $settings, $updatedBy);
    }

    public function flushCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget($this->cacheKey($key));
            return;
        }

        Cache::forget($this->cacheKey(self::HEADER_KEY));
        Cache::forget($this->cacheKey(self::NAVIGATION_KEY));
        Cache::forget($this->cacheKey(self::FOOTER_KEY));
    }

    /** @return array<string, mixed> */
    private function get(string $key): array
    {
        return Cache::remember(
            $this->cacheKey($key),
            self::CACHE_SECONDS,
            function () use ($key): array {
                $stored = StorefrontSetting::query()->where('key', $key)->first()?->settings;
                $stored = is_array($stored) ? $stored : [];
                $stored = $this->normalizeLegacyShape($key, $stored);
                $defaults = $this->defaults($key);

                $settings = $this->mergeDefaults(
                    $defaults,
                    Arr::only($stored, array_keys($defaults))
                );

                return $this->normalizeManagedMediaUrls($settings);
            }
        );
    }

    /** @param array<string, mixed> $settings */
    private function update(string $key, array $settings, ?int $updatedBy): StorefrontSetting
    {
        $defaults = $this->defaults($key);
        $clean = $this->mergeDefaults(
            $defaults,
            Arr::only($settings, array_keys($defaults))
        );

        $model = StorefrontSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'settings' => $clean,
                'updated_by' => $updatedBy,
            ]
        );

        $this->flushCache($key);

        return $model;
    }

    /**
     * Merge associative setting groups while treating list arrays as complete
     * values. This allows repeaters to remove and reorder items without old
     * defaults being merged back by numeric index.
     *
     * @param  array<string|int, mixed>  $defaults
     * @param  array<string|int, mixed>  $overrides
     * @return array<string|int, mixed>
     */
    private function mergeDefaults(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (
                is_array($value)
                && isset($defaults[$key])
                && is_array($defaults[$key])
                && ! array_is_list($value)
                && ! array_is_list($defaults[$key])
            ) {
                $defaults[$key] = $this->mergeDefaults($defaults[$key], $value);
                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }

    /** @param array<string|int, mixed> $settings @return array<string|int, mixed> */
    private function normalizeManagedMediaUrls(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $settings[$key] = $this->normalizeManagedMediaUrls($value);
                continue;
            }

            if (! in_array((string) $key, ['icon', 'logo', 'image'], true) || ! is_string($value)) {
                continue;
            }

            $path = PublicMedia::storedPathFromUrl($value);
            if ($path !== null && str_starts_with($path, 'storefront/settings/')) {
                $settings[$key] = PublicMedia::storedPathUrl($path);
            }
        }

        return $settings;
    }

    /** @return array<string, mixed> */
    private function defaults(string $key): array
    {
        return match ($key) {
            self::HEADER_KEY => [
                'branding' => [
                    'logo' => (string) config('storefront.vue_logo', '/images/storefront-vue/brand/nextplay-wordmark.png'),
                    'logo_alt' => (string) config('storefront.name', 'NextPlay Sportswear'),
                ],
                'announcements' => [
                    [
                        'enabled' => true,
                        'text' => 'Shop $200 and get free delivery',
                        'url' => null,
                        'dismissible' => true,
                    ],
                ],
                'utility_links' => [
                    [
                        'enabled' => true,
                        'label' => 'Track Your Order',
                        'url' => '/account/orders',
                        'icon' => 'package',
                    ],
                    [
                        'enabled' => true,
                        'label' => 'Delivery & Returns',
                        'url' => '/shipping-delivery',
                        'icon' => 'delivery',
                    ],
                    [
                        'enabled' => true,
                        'label' => 'Contact Support',
                        'url' => '/contact-us',
                        'icon' => 'headphones',
                    ],
                ],
                'actions' => [
                    'search' => [
                        'enabled' => true,
                        'label' => 'Search',
                        'url' => '/products',
                    ],
                    'account_enabled' => true,
                    'wishlist_enabled' => true,
                    'cart_enabled' => true,
                    'quote' => [
                        'enabled' => true,
                        'label' => 'GET A QUOTE',
                        'url' => '/bulk-quote',
                    ],
                ],
            ],
            self::NAVIGATION_KEY => [
                'items' => [
                    [
                        'enabled' => true,
                        'label' => 'SHOP',
                        'url' => '/products',
                        'target' => '_self',
                        'mega_menu' => [
                            'enabled' => true,
                            'top_choices' => [
                                'enabled' => true,
                                'eyebrow' => 'Top Choices',
                                'links' => [
                                    ['enabled' => true, 'label' => 'BEST SELLERS', 'url' => '/products?sort=best-selling'],
                                    ['enabled' => true, 'label' => 'ON SALE', 'url' => '/products?on_sale=1'],
                                    ['enabled' => true, 'label' => 'FEATURED FOR YOU', 'url' => '/products?featured=1'],
                                    ['enabled' => true, 'label' => 'SUMMER 2026', 'url' => '/products?q=summer'],
                                ],
                            ],
                            'columns' => [
                                [
                                    'enabled' => true,
                                    'title' => 'NEW ARRIVALS',
                                    'links' => [
                                        ['enabled' => true, 'label' => "New for Men's", 'url' => '/products?q=men&sort=newest'],
                                        ['enabled' => true, 'label' => "New for Women's", 'url' => '/products?q=women&sort=newest'],
                                        ['enabled' => true, 'label' => "New for Kid's", 'url' => '/products?q=kids&sort=newest'],
                                        ['enabled' => true, 'label' => 'New in Accessories', 'url' => '/products?q=accessories&sort=newest'],
                                    ],
                                ],
                                [
                                    'enabled' => true,
                                    'title' => 'MEN',
                                    'links' => [
                                        ['enabled' => true, 'label' => 'Jerseys', 'url' => '/products?q=men+jersey'],
                                        ['enabled' => true, 'label' => 'Shorts', 'url' => '/products?q=men+shorts'],
                                        ['enabled' => true, 'label' => 'Training Wear', 'url' => '/products?q=men+training'],
                                        ['enabled' => true, 'label' => 'Uniforms', 'url' => '/products?q=men+uniform'],
                                        ['enabled' => true, 'label' => 'Accessories', 'url' => '/products?q=men+accessories'],
                                    ],
                                ],
                                [
                                    'enabled' => true,
                                    'title' => 'WOMEN',
                                    'links' => [
                                        ['enabled' => true, 'label' => 'Jerseys', 'url' => '/products?q=women+jersey'],
                                        ['enabled' => true, 'label' => 'Shorts', 'url' => '/products?q=women+shorts'],
                                        ['enabled' => true, 'label' => 'Training Wear', 'url' => '/products?q=women+training'],
                                        ['enabled' => true, 'label' => 'Uniforms', 'url' => '/products?q=women+uniform'],
                                        ['enabled' => true, 'label' => 'Accessories', 'url' => '/products?q=women+accessories'],
                                    ],
                                ],
                                [
                                    'enabled' => true,
                                    'title' => 'KIDS',
                                    'links' => [
                                        ['enabled' => true, 'label' => 'Jerseys', 'url' => '/products?q=kids+jersey'],
                                        ['enabled' => true, 'label' => 'Shorts', 'url' => '/products?q=kids+shorts'],
                                        ['enabled' => true, 'label' => 'Training Wear', 'url' => '/products?q=kids+training'],
                                        ['enabled' => true, 'label' => 'Uniforms', 'url' => '/products?q=kids+uniform'],
                                        ['enabled' => true, 'label' => 'Accessories', 'url' => '/products?q=kids+accessories'],
                                    ],
                                ],
                            ],
                            'promo' => [
                                'enabled' => true,
                                'image' => '/images/storefront-vue/navigation/shop-mega-promo.jpg',
                                'alt' => 'Basketball players competing on an outdoor court',
                                'label' => 'Shop All Products',
                                'url' => '/products',
                            ],
                        ],
                    ],
                    [
                        'enabled' => true,
                        'label' => 'SPORTS',
                        'url' => '/categories',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ],
                    [
                        'enabled' => true,
                        'label' => 'MEN',
                        'url' => '/men',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ],
                    [
                        'enabled' => true,
                        'label' => 'WOMEN',
                        'url' => '/products?q=women',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ],
                    [
                        'enabled' => true,
                        'label' => 'KIDS',
                        'url' => '/products?q=kids',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ],
                    [
                        'enabled' => true,
                        'label' => 'CUSTOM TEAMWEAR',
                        'url' => '/bulk-quote',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ],
                    [
                        'enabled' => true,
                        'label' => 'EXPLORE',
                        'url' => '/about-us',
                        'target' => '_self',
                        'mega_menu' => ['enabled' => false],
                    ],
                ],
            ],
            self::FOOTER_KEY => [
                'contact' => [
                    'address' => '27 Shawptak square, London, UK',
                    'email' => 'support@example.com',
                    'phone' => '+1000 000 0000',
                ],
                'columns' => [
                    [
                        'enabled' => true,
                        'title' => 'Quick Links',
                        'items' => [
                            ['enabled' => true, 'label' => 'Wishlist', 'url' => '/wishlist', 'icon' => null],
                            ['enabled' => true, 'label' => 'My Account', 'url' => '/account', 'icon' => null],
                            ['enabled' => true, 'label' => 'Offers', 'url' => '/offers', 'icon' => null],
                            ['enabled' => true, 'label' => 'Sitemap', 'url' => '/sitemap.xml', 'icon' => null],
                        ],
                    ],
                    [
                        'enabled' => true,
                        'title' => 'Help & Support',
                        'items' => [
                            ['enabled' => true, 'label' => 'FAQs', 'url' => '/help-center', 'icon' => null],
                            ['enabled' => true, 'label' => 'Delivery & Returns', 'url' => '/shipping-delivery', 'icon' => null],
                            ['enabled' => true, 'label' => 'Size Guide', 'url' => '/size-guide', 'icon' => null],
                            ['enabled' => true, 'label' => 'Track Your Order', 'url' => '/account/orders', 'icon' => null],
                        ],
                    ],
                    [
                        'enabled' => true,
                        'title' => 'Customer Service',
                        'items' => [
                            ['enabled' => true, 'label' => 'Contact Us', 'url' => '/contact-us', 'icon' => null],
                            ['enabled' => true, 'label' => 'Get a Quote', 'url' => '/bulk-quote', 'icon' => null],
                        ],
                    ],
                ],
                'club' => [
                    'enabled' => true,
                    'title' => 'JOIN NEXTPLAY CLUB & GET 20% OFF',
                    'button_label' => 'SIGN UP',
                    'button_url' => '/register',
                ],
                'social' => [
                    'enabled' => true,
                    'label' => 'Follow Us :',
                    'links' => [
                        ['enabled' => true, 'label' => 'YouTube', 'url' => 'https://www.youtube.com/@nextplaysportswear', 'icon' => 'youtube'],
                        ['enabled' => true, 'label' => 'Instagram', 'url' => 'https://www.instagram.com/nextplaysportswear/', 'icon' => 'instagram'],
                        ['enabled' => true, 'label' => 'Facebook', 'url' => 'https://www.facebook.com/nextplaysportswear', 'icon' => 'facebook'],
                        ['enabled' => true, 'label' => 'TikTok', 'url' => 'https://www.tiktok.com/@nextplaysportswear', 'icon' => 'tiktok'],
                    ],
                ],
                'legal' => [
                    'copyright' => '© {year} Nextplay Sportswear',
                    'links' => [
                        ['enabled' => true, 'label' => 'Privacy Policy', 'url' => '/privacy-policy', 'icon' => null],
                        ['enabled' => true, 'label' => 'Terms & Conditions', 'url' => '/terms-conditions', 'icon' => null],
                        ['enabled' => true, 'label' => 'Cookie Policy', 'url' => '/cookie-policy', 'icon' => null],
                    ],
                ],
                'payments' => [
                    'enabled' => true,
                    'label' => 'Secured by Stripe:',
                ],
            ],
            default => [],
        };
    }

    /**
     * Existing installs may already contain the first version of header/footer
     * settings. Normalize that JSON into the dynamic repeater contract so an
     * upgrade does not discard admin-entered content.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private function normalizeLegacyShape(string $key, array $stored): array
    {
        if ($key === self::HEADER_KEY) {
            if (! isset($stored['announcements']) && isset($stored['announcement']) && is_array($stored['announcement'])) {
                $stored['announcements'] = [$stored['announcement']];
            }

            if (isset($stored['utility_links']) && is_array($stored['utility_links']) && ! array_is_list($stored['utility_links'])) {
                $icons = [
                    'track_order' => 'package',
                    'delivery_returns' => 'delivery',
                    'contact_support' => 'headphones',
                ];
                $links = [];
                foreach ($stored['utility_links'] as $name => $link) {
                    if (! is_array($link)) {
                        continue;
                    }
                    $link['icon'] ??= $icons[(string) $name] ?? null;
                    $links[] = $link;
                }
                $stored['utility_links'] = $links;
            }

            unset($stored['announcement']);

            return $stored;
        }

        if ($key === self::FOOTER_KEY) {
            if (isset($stored['columns']) && is_array($stored['columns']) && ! array_is_list($stored['columns'])) {
                $columns = [];
                foreach ($stored['columns'] as $column) {
                    if (! is_array($column)) {
                        continue;
                    }
                    $items = [];
                    foreach ((array) ($column['items'] ?? []) as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $items[] = [
                            'enabled' => (bool) ($item['enabled'] ?? true),
                            'label' => $item['label'] ?? null,
                            'url' => $item['url'] ?? null,
                            'icon' => $item['icon'] ?? null,
                        ];
                    }
                    $columns[] = [
                        'enabled' => (bool) ($column['enabled'] ?? true),
                        'title' => $column['title'] ?? null,
                        'items' => $items,
                    ];
                }
                $stored['columns'] = $columns;
            }

            if (isset($stored['social']) && is_array($stored['social']) && ! isset($stored['social']['links'])) {
                $social = $stored['social'];
                $links = [];
                foreach ([
                    'youtube' => 'YouTube',
                    'instagram' => 'Instagram',
                    'facebook' => 'Facebook',
                    'tiktok' => 'TikTok',
                ] as $network => $label) {
                    $url = $social[$network.'_url'] ?? null;
                    if (is_string($url) && trim($url) !== '') {
                        $links[] = [
                            'enabled' => true,
                            'label' => $label,
                            'url' => $url,
                            'icon' => $network,
                        ];
                    }
                    unset($social[$network.'_url']);
                }
                $social['links'] = $links;
                $stored['social'] = $social;
            }

            if (isset($stored['legal']) && is_array($stored['legal']) && ! isset($stored['legal']['links'])) {
                $legal = $stored['legal'];
                $links = [];
                foreach (['privacy', 'terms', 'cookie'] as $name) {
                    $label = $legal[$name.'_label'] ?? null;
                    $url = $legal[$name.'_url'] ?? null;
                    if (is_string($label) && trim($label) !== '' && is_string($url) && trim($url) !== '') {
                        $links[] = [
                            'enabled' => true,
                            'label' => $label,
                            'url' => $url,
                            'icon' => null,
                        ];
                    }
                    unset($legal[$name.'_label'], $legal[$name.'_url']);
                }
                $legal['links'] = $links;
                $stored['legal'] = $legal;
            }
        }

        return $stored;
    }

    private function cacheKey(string $key): string
    {
        return 'storefront.settings.v2.'.$key;
    }
}
