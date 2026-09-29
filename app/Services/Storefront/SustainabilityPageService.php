<?php

namespace App\Services\Storefront;

use App\Models\SustainabilityPageSetting;
use App\Support\PublicMedia;
use App\Support\PublicUrl;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SustainabilityPageService
{
    /** @return array<string,mixed> */
    public function defaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'NEXTPLAY SPORTSWEAR',
                'title' => 'SUSTAINABILITY',
                'subtitle' => 'Making thoughtful choices, one step at a time.',
            ],
            'approach' => [
                'title' => 'OUR APPROACH',
                'description' => 'We believe sportswear should be made with care for the people who wear it and the resources used to make it. We are working to learn more, ask better questions and make practical improvements.',
            ],
            'features' => [
                ['id' => 'materials_waste', 'heading' => 'MATERIALS & WASTE', 'description' => 'We look for ways to use materials efficiently and reduce avoidable waste in custom orders.', 'image_path' => null, 'image_alt' => 'Sportswear fabric being carefully cut for production', 'fallback_image' => 'images/sustainability/materials-waste.webp'],
                ['id' => 'people_partners', 'heading' => 'PEOPLE & PARTNERS', 'description' => 'We ask our production partners about working practices and aim to build clear expectations over time.', 'image_path' => null, 'image_alt' => 'Sportswear being sewn by a production partner', 'fallback_image' => 'images/sustainability/people-partners.webp'],
                ['id' => 'packaging_delivery', 'heading' => 'PACKAGING & DELIVERY', 'description' => 'We are reviewing packaging choices and how orders travel so we can identify practical changes.', 'image_path' => null, 'image_alt' => 'Sportswear packed in cardboard boxes for delivery', 'fallback_image' => 'images/sustainability/packaging-delivery.webp'],
            ],
            'informed' => [
                'title' => 'KEEPING YOU INFORMED',
                'subtitle' => 'We will share specific goals, actions and evidence here as they become available.',
                'cards' => [
                    ['id' => 'materials', 'title' => 'Materials', 'description' => 'More information to come.', 'icon_path' => null, 'icon_alt' => 'Materials leaf icon', 'fallback_icon' => 'leaf'],
                    ['id' => 'partners', 'title' => 'Partners', 'description' => 'More information to come.', 'icon_path' => null, 'icon_alt' => 'Partners people icon', 'fallback_icon' => 'partners'],
                    ['id' => 'packaging', 'title' => 'Packaging', 'description' => 'More information to come.', 'icon_path' => null, 'icon_alt' => 'Packaging box icon', 'fallback_icon' => 'box'],
                ],
            ],
            'cta' => [
                'title' => 'Questions about a product?',
                'description' => 'Our team is here to help with product selection, customisation options, bulk orders and more.',
                'primary_label' => 'CONTACT US',
                'primary_url' => '/contact-us',
                'secondary_label' => 'SHOP PRODUCTS',
                'secondary_url' => '/products',
            ],
            'seo' => [
                'title' => 'Sustainability | '.config('storefront.name'),
                'description' => 'Learn about NextPlay Sportswear sustainability priorities across materials, production partners, packaging and delivery.',
            ],
        ];
    }

    /** @return array<string,mixed> */
    public function settings(): array
    {
        $defaults = $this->defaults();
        $record = null;
        try {
            if (Schema::hasTable('sustainability_page_settings')) {
                $record = SustainabilityPageSetting::query()->first();
            }
        } catch (Throwable) {
            $record = null;
        }

        $page = $defaults;
        if ($record) {
            foreach (['hero', 'approach', 'cta', 'seo'] as $section) {
                $persisted = is_array($record->{$section}) ? $record->{$section} : [];
                $page[$section] = $this->mergeKnownShape($defaults[$section], $persisted);
            }
            $page['features'] = $this->normalizeFixedItems($defaults['features'], is_array($record->features) ? $record->features : []);
            $informedPersisted = is_array($record->informed) ? $record->informed : [];
            $page['informed'] = $this->mergeKnownShape(array_diff_key($defaults['informed'], ['cards' => true]), array_diff_key($informedPersisted, ['cards' => true]));
            $page['informed']['cards'] = $this->normalizeFixedItems($defaults['informed']['cards'], is_array($informedPersisted['cards'] ?? null) ? $informedPersisted['cards'] : []);
        }

        foreach ($page['features'] as $i => $feature) {
            $page['features'][$i]['fallback_image'] = $defaults['features'][$i]['fallback_image'];
        }
        foreach ($page['informed']['cards'] as $i => $card) {
            $page['informed']['cards'][$i]['fallback_icon'] = $defaults['informed']['cards'][$i]['fallback_icon'];
        }

        foreach (['cta.primary_url', 'cta.secondary_url'] as $path) {
            $value = data_get($page, $path);
            if (! is_string($value) || ! PublicUrl::isAllowed($value) || trim($value) === '#') {
                data_set($page, $path, data_get($defaults, $path));
            }
        }

        return $this->resolveMedia($page);
    }

    /** @param array<int,array<string,mixed>> $defaults @param array<int,mixed> $persisted */
    private function normalizeFixedItems(array $defaults, array $persisted): array
    {
        $byId = [];
        foreach ($persisted as $item) {
            if (! is_array($item) || ! isset($item['id'])) continue;
            $id = (string) $item['id'];
            if ($id === '' || isset($byId[$id])) continue;
            $byId[$id] = $item;
        }
        return array_map(function (array $default) use ($byId): array {
            $merged = $this->mergeKnownShape($default, $byId[(string) $default['id']] ?? []);
            $merged['id'] = $default['id'];
            if (isset($default['fallback_image'])) $merged['fallback_image'] = $default['fallback_image'];
            if (isset($default['fallback_icon'])) $merged['fallback_icon'] = $default['fallback_icon'];
            return $merged;
        }, $defaults);
    }

    /** @param array<string,mixed> $defaults @param array<string,mixed> $persisted */
    private function mergeKnownShape(array $defaults, array $persisted): array
    {
        $result = $defaults;
        foreach ($defaults as $key => $default) {
            if (! array_key_exists($key, $persisted)) continue;
            $value = $persisted[$key];
            if (is_array($default)) {
                if (is_array($value)) $result[$key] = $this->mergeKnownShape($default, $value);
            } elseif ($default === null) {
                if ($value === null || is_string($value)) $result[$key] = $value;
            } elseif (gettype($value) === gettype($default)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /** @param array<string,mixed> $page @return array<string,mixed> */
    private function resolveMedia(array $page): array
    {
        foreach ($page['features'] as &$feature) {
            $feature['image_url'] = $this->customUrl($feature['image_path'] ?? null) ?? asset((string) $feature['fallback_image']);
            $feature['has_custom_image'] = $this->validStoredPath($feature['image_path'] ?? null) !== null;
        }
        unset($feature);
        foreach ($page['informed']['cards'] as &$card) {
            $card['icon_url'] = $this->customUrl($card['icon_path'] ?? null);
            $card['has_custom_icon'] = $card['icon_url'] !== null;
        }
        unset($card);
        return $page;
    }

    private function customUrl(mixed $path): ?string
    {
        $valid = $this->validStoredPath($path);
        return $valid ? PublicMedia::storedPathUrl($valid) : null;
    }

    private function validStoredPath(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') return null;
        $normalized = PublicMedia::normalizePath($path);
        if (! $this->isSafeSustainabilityPath($normalized)) return null;
        try { return Storage::disk('public')->exists($normalized) ? $normalized : null; } catch (Throwable) { return null; }
    }

    private function isSafeSustainabilityPath(string $path): bool
    {
        if (! str_starts_with($path, 'sustainability-page/') || str_contains($path, "\0")) return false;
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') return false;
        }
        return count(explode('/', $path)) >= 3;
    }
}
