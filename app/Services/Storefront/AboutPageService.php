<?php

namespace App\Services\Storefront;

use App\Models\AboutPageSetting;
use App\Support\PublicMedia;
use App\Support\PublicUrl;
use Illuminate\Support\Facades\Storage;

class AboutPageService
{
    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'NEXTPLAY SPORTSWEAR',
                'title' => 'ABOUT NEXTPLAY',
                'description' => 'Sportswear for teams, clubs and people who love to play.',
            ],
            'introduction' => [
                'title' => 'We help teams bring their ideas to life through custom sportswear and everyday performance gear.',
                'description' => 'From your first design choice to the final order, our aim is to make the process clear and easy to follow.',
                'image_path' => null,
                'image_alt' => 'Four athletes wearing NextPlay team uniforms together',
                'fallback_asset' => 'images/storefront/about/team-intro.webp',
            ],
            'what_we_do' => [
                'title' => 'WHAT WE DO',
                'cards' => [
                    [
                        'id' => 'custom-teamwear',
                        'title' => 'Custom Teamwear',
                        'description' => 'Personalised jerseys, uniforms and apparel for your team or club.',
                        'icon_path' => null,
                        'icon_alt' => 'Custom teamwear icon',
                        'fallback_icon' => 'teamwear',
                    ],
                    [
                        'id' => 'sportswear-gear',
                        'title' => 'Sportswear & Gear',
                        'description' => 'A wide range of sportswear and accessories for on and off the field.',
                        'icon_path' => null,
                        'icon_alt' => 'Sportswear and gear icon',
                        'fallback_icon' => 'sportswear',
                    ],
                    [
                        'id' => 'bulk-orders',
                        'title' => 'Bulk Orders',
                        'description' => 'Simple options for larger orders for teams, clubs, events and organisations.',
                        'icon_path' => null,
                        'icon_alt' => 'Bulk orders icon',
                        'fallback_icon' => 'bulk',
                    ],
                ],
            ],
            'how_we_work' => [
                'title' => 'HOW WE WORK',
                'steps' => [
                    [
                        'id' => 'choose-product',
                        'number' => '1',
                        'title' => 'Choose a product',
                        'description' => 'Browse our range and find the styles that suit your team or activity.',
                        'icon_path' => null,
                        'icon_alt' => 'Choose a product icon',
                        'fallback_icon' => 'cart',
                    ],
                    [
                        'id' => 'personalise',
                        'number' => '2',
                        'title' => 'Personalise it',
                        'description' => 'Add your team name, numbers, colours and other custom options.',
                        'icon_path' => null,
                        'icon_alt' => 'Personalise product icon',
                        'fallback_icon' => 'pencil',
                    ],
                    [
                        'id' => 'review-details',
                        'number' => '3',
                        'title' => 'Review your details',
                        'description' => 'Check your selections, sizes and quantities before adding to your order.',
                        'icon_path' => null,
                        'icon_alt' => 'Review order details icon',
                        'fallback_icon' => 'review',
                    ],
                    [
                        'id' => 'place-order',
                        'number' => '4',
                        'title' => 'Place your order',
                        'description' => "Complete your purchase and we'll get your order in progress.",
                        'icon_path' => null,
                        'icon_alt' => 'Place your order icon',
                        'fallback_icon' => 'box',
                    ],
                ],
            ],
            'gallery' => [
                'items' => [
                    [
                        'id' => 'team',
                        'image_path' => null,
                        'image_alt' => 'Team members in blue jerseys standing together',
                        'fallback_asset' => 'images/storefront/about/gallery-team.webp',
                    ],
                    [
                        'id' => 'fabric',
                        'image_path' => null,
                        'image_alt' => 'Close-up of black and orange sportswear fabric',
                        'fallback_asset' => 'images/storefront/about/gallery-fabric.webp',
                    ],
                    [
                        'id' => 'number',
                        'image_path' => null,
                        'image_alt' => 'Green sports jersey with number 23',
                        'fallback_asset' => 'images/storefront/about/gallery-number.webp',
                    ],
                    [
                        'id' => 'celebration',
                        'image_path' => null,
                        'image_alt' => 'Athletes in black and pink uniforms celebrating together',
                        'fallback_asset' => 'images/storefront/about/gallery-celebration.webp',
                    ],
                ],
            ],
            'cta' => [
                'eyebrow' => 'MADE FOR YOUR TEAM',
                'title' => 'Explore custom options for your club, event or organisation.',
                'primary_label' => 'EXPLORE PRODUCTS',
                'primary_url' => '/products',
                'secondary_label' => 'REQUEST A BULK QUOTE',
                'secondary_url' => '/bulk-quote',
            ],
            'help' => [
                'title' => 'NEED HELP?',
                'description' => 'Our team is here to help with product selection, customisation options, bulk orders and more.',
                'icon_path' => null,
                'icon_alt' => 'Customer support headset icon',
                'fallback_icon' => 'headset',
                'button_label' => 'Contact Us',
                'button_url' => '/contact-us',
            ],
            'seo' => [
                'title' => 'About NextPlay | '.config('storefront.name'),
                'description' => 'Learn how NextPlay Sportswear helps teams, clubs, events and organisations order custom sportswear and performance gear with a clear, simple process.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $defaults = $this->defaults();
        $record = AboutPageSetting::query()->first();

        $about = $defaults;
        if ($record) {
            foreach (['hero', 'introduction', 'cta', 'help', 'seo'] as $section) {
                $persisted = is_array($record->{$section}) ? $record->{$section} : [];
                $about[$section] = $this->mergeKnownShape($defaults[$section], $persisted);
            }

            $about['what_we_do'] = $this->normalizeFixedSection(
                $defaults['what_we_do'],
                is_array($record->what_we_do) ? $record->what_we_do : [],
                'cards'
            );
            $about['how_we_work'] = $this->normalizeFixedSection(
                $defaults['how_we_work'],
                is_array($record->how_we_work) ? $record->how_we_work : [],
                'steps'
            );
            $about['gallery'] = $this->normalizeFixedSection(
                $defaults['gallery'],
                is_array($record->gallery) ? $record->gallery : [],
                'items'
            );
        }

        $about['introduction']['fallback_asset'] = $defaults['introduction']['fallback_asset'];
        $about['help']['fallback_icon'] = $defaults['help']['fallback_icon'];

        return $this->resolveMedia($this->normalizeDestinations($about, $defaults));
    }

    /** @param array<string, mixed> $defaults @param array<string, mixed> $persisted */
    private function normalizeFixedSection(array $defaults, array $persisted, string $collectionKey): array
    {
        $sectionDefaults = array_diff_key($defaults, [$collectionKey => true]);
        $sectionPersisted = array_diff_key($persisted, [$collectionKey => true]);
        $result = array_replace($defaults, $this->mergeKnownShape($sectionDefaults, $sectionPersisted));
        $persistedItems = is_array($persisted[$collectionKey] ?? null) ? $persisted[$collectionKey] : [];
        $byId = [];

        foreach ($persistedItems as $item) {
            if (! is_array($item) || ! isset($item['id'])) {
                continue;
            }

            $id = (string) $item['id'];
            if ($id === '' || array_key_exists($id, $byId)) {
                continue;
            }

            $byId[$id] = $item;
        }

        $result[$collectionKey] = array_map(function (array $defaultItem) use ($byId): array {
            $id = (string) $defaultItem['id'];
            $persistedItem = $byId[$id] ?? [];
            $merged = $this->mergeKnownShape($defaultItem, is_array($persistedItem) ? $persistedItem : []);

            // Stable identity and fallback metadata are code-owned and cannot be changed by malformed data.
            $merged['id'] = $defaultItem['id'];
            if (array_key_exists('fallback_icon', $defaultItem)) {
                $merged['fallback_icon'] = $defaultItem['fallback_icon'];
            }
            if (array_key_exists('fallback_asset', $defaultItem)) {
                $merged['fallback_asset'] = $defaultItem['fallback_asset'];
            }

            return $merged;
        }, $defaults[$collectionKey]);

        return $result;
    }

    /** @param array<string, mixed> $about @param array<string, mixed> $defaults @return array<string, mixed> */
    private function normalizeDestinations(array $about, array $defaults): array
    {
        foreach (['cta.primary_url', 'cta.secondary_url', 'help.button_url'] as $path) {
            $value = data_get($about, $path);
            if (! is_string($value) || ! $this->isAllowedDestination($value)) {
                data_set($about, $path, data_get($defaults, $path));
            }
        }

        return $about;
    }

    private function isAllowedDestination(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || str_starts_with($value, '#')) {
            return false;
        }

        return PublicUrl::isAllowed($value);
    }

    /** @param array<string, mixed> $defaults @param array<string, mixed> $persisted @return array<string, mixed> */
    private function mergeKnownShape(array $defaults, array $persisted): array
    {
        $result = $defaults;

        foreach ($defaults as $key => $defaultValue) {
            if (! array_key_exists($key, $persisted)) {
                continue;
            }

            $value = $persisted[$key];
            if (is_array($defaultValue)) {
                if (is_array($value)) {
                    $result[$key] = $this->mergeKnownShape($defaultValue, $value);
                }

                continue;
            }

            if ($defaultValue === null) {
                if ($value === null || is_string($value)) {
                    $result[$key] = $value;
                }

                continue;
            }

            if (gettype($value) === gettype($defaultValue)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $about @return array<string, mixed> */
    private function resolveMedia(array $about): array
    {
        $about['introduction']['image_url'] = $this->imageUrl(
            $about['introduction']['image_path'] ?? null,
            (string) $about['introduction']['fallback_asset']
        );
        $about['introduction']['has_custom_image'] = $this->validStoredPath($about['introduction']['image_path'] ?? null) !== null;

        foreach ($about['what_we_do']['cards'] as &$card) {
            $card['icon_url'] = $this->customUrl($card['icon_path'] ?? null);
            $card['has_custom_icon'] = $card['icon_url'] !== null;
        }
        unset($card);

        foreach ($about['how_we_work']['steps'] as &$step) {
            $step['icon_url'] = $this->customUrl($step['icon_path'] ?? null);
            $step['has_custom_icon'] = $step['icon_url'] !== null;
        }
        unset($step);

        foreach ($about['gallery']['items'] as &$item) {
            $item['image_url'] = $this->imageUrl($item['image_path'] ?? null, (string) $item['fallback_asset']);
            $item['has_custom_image'] = $this->validStoredPath($item['image_path'] ?? null) !== null;
        }
        unset($item);

        $about['help']['icon_url'] = $this->customUrl($about['help']['icon_path'] ?? null);
        $about['help']['has_custom_icon'] = $about['help']['icon_url'] !== null;

        return $about;
    }

    private function imageUrl(mixed $path, string $fallbackAsset): string
    {
        return $this->customUrl($path) ?? asset($fallbackAsset);
    }

    private function customUrl(mixed $path): ?string
    {
        $validPath = $this->validStoredPath($path);

        return $validPath !== null ? PublicMedia::storedPathUrl($validPath) : null;
    }

    private function validStoredPath(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $normalized = PublicMedia::normalizePath($path);
        if (! $this->isSafeAboutPath($normalized)) {
            return null;
        }

        try {
            return Storage::disk('public')->exists($normalized) ? $normalized : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isSafeAboutPath(string $path): bool
    {
        if (! str_starts_with($path, 'about-page/') || str_contains($path, "\0")) {
            return false;
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return count($segments) >= 3;
    }
}
