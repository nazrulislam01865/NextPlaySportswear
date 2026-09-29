<?php

namespace App\Services\Storefront;

use App\Models\ShippingDeliveryPageSetting;
use App\Support\PublicMedia;
use App\Support\PublicUrl;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ShippingDeliveryPageService
{
    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'NEXTPLAY SPORTSWEAR',
                'title' => 'ORDER INFORMATION',
                'subtitle' => 'A clear guide to ordering custom sportswear with NextPlay.',
            ],
            'tabs' => [
                ['id' => 'before-you-order', 'label' => 'Before You Order'],
                ['id' => 'artwork-customisation', 'label' => 'Artwork & Customisation'],
                ['id' => 'after-you-order', 'label' => 'After You Order'],
                ['id' => 'delivery', 'label' => 'Delivery'],
                ['id' => 'help', 'label' => 'Help'],
            ],
            'delivery_intro' => [
                'title' => 'DELIVERY',
                'subtitle' => 'See delivery options before checkout and follow your order after dispatch.',
            ],
            'info_cards' => [
                'cards' => [
                    [
                        'id' => 'before-you-pay',
                        'title' => 'BEFORE YOU PAY',
                        'description' => 'Review the available delivery options and total cost at checkout before placing your order.',
                        'icon_path' => null,
                        'icon_alt' => 'Shopping cart icon',
                        'fallback_icon' => 'cart',
                    ],
                    [
                        'id' => 'after-dispatch',
                        'title' => 'AFTER DISPATCH',
                        'description' => 'If tracking is available for your shipment, you can find the latest details in Track Your Order.',
                        'icon_path' => null,
                        'icon_alt' => 'Parcel icon',
                        'fallback_icon' => 'box',
                    ],
                ],
            ],
            'delivery_steps' => [
                'title' => 'HOW DELIVERY WORKS',
                'steps' => [
                    [
                        'id' => 'confirm-address',
                        'number' => '1',
                        'title' => 'Confirm your address',
                        'description' => 'Enter a complete and accurate shipping address at checkout.',
                    ],
                    [
                        'id' => 'choose-delivery-option',
                        'number' => '2',
                        'title' => 'Choose an available delivery option',
                        'description' => 'Review the delivery options and total cost shown for your order.',
                    ],
                    [
                        'id' => 'follow-dispatch-updates',
                        'number' => '3',
                        'title' => 'Follow updates after dispatch',
                        'description' => 'If tracking is available, use Track Your Order to see the latest details.',
                    ],
                ],
            ],
            'notice' => [
                'text' => 'Production time and delivery time are different. Any estimate should be shown for your specific order.',
                'icon_path' => null,
                'icon_alt' => 'Information icon',
                'fallback_icon' => 'info',
            ],
            'faqs' => [
                'title' => 'DELIVERY QUESTIONS',
                'subtitle' => 'Find quick answers to common questions about delivery.',
                'items' => [
                    [
                        'id' => 'delivery-costs',
                        'question' => 'When will I see delivery costs?',
                        'answer' => 'The applicable delivery charges are shown before the order is placed.',
                    ],
                    [
                        'id' => 'change-address',
                        'question' => 'Can I change my delivery address?',
                        'answer' => 'Contact support promptly; changes depend on your order stage and whether it has been dispatched.',
                    ],
                    [
                        'id' => 'tracking-number',
                        'question' => 'Where is my tracking number?',
                        'answer' => 'If available, it appears after dispatch on Track Your Order.',
                    ],
                    [
                        'id' => 'multiple-locations',
                        'question' => 'Can a team order ship to multiple locations?',
                        'answer' => 'Ask for a bulk quote so we can review the request.',
                    ],
                ],
            ],
            'address_checklist' => [
                'title' => 'SHIPPING ADDRESS CHECKLIST',
                'subtitle' => 'Make sure your shipping address is complete and accurate to help avoid delays.',
                'items' => [
                    [
                        'id' => 'recipient-name',
                        'title' => 'Recipient name',
                        'description' => 'Use the full name of the person receiving the order.',
                        'icon_path' => null,
                        'icon_alt' => 'Recipient icon',
                        'fallback_icon' => 'user',
                    ],
                    [
                        'id' => 'full-address',
                        'title' => 'Full address',
                        'description' => 'Include building number, street, and any additional address information (e.g. unit, suite).',
                        'icon_path' => null,
                        'icon_alt' => 'Address document icon',
                        'fallback_icon' => 'document',
                    ],
                    [
                        'id' => 'postcode',
                        'title' => 'Postcode',
                        'description' => 'Enter the correct postcode for the delivery address.',
                        'icon_path' => null,
                        'icon_alt' => 'Location pin icon',
                        'fallback_icon' => 'pin',
                    ],
                    [
                        'id' => 'contact-details',
                        'title' => 'Contact details',
                        'description' => 'Provide a phone number or email in case the delivery team needs to contact you.',
                        'icon_path' => null,
                        'icon_alt' => 'Phone icon',
                        'fallback_icon' => 'phone',
                    ],
                ],
            ],
            'cta' => [
                'title' => 'CHECK YOUR ORDER STATUS',
                'description' => 'Track your order after dispatch to see the latest delivery updates.',
                'primary_label' => 'TRACK YOUR ORDER',
                'primary_url' => '/track-order',
                'policy_label' => 'Shipping & Delivery policy',
                'policy_url' => '/terms-conditions',
            ],
            'seo' => [
                'title' => 'Shipping & Delivery | '.config('storefront.name'),
                'description' => 'Review NextPlay Sportswear delivery information, address guidance, tracking help and shipping answers before and after your order.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $defaults = $this->defaults();
        $page = $defaults;

        if (! Schema::hasTable('shipping_delivery_page_settings')) {
            return $this->resolveMedia($this->normalizeDestinations($page, $defaults));
        }

        $record = ShippingDeliveryPageSetting::query()->first();
        if ($record) {
            foreach (['hero', 'delivery_intro', 'notice', 'cta', 'seo'] as $section) {
                $persisted = is_array($record->{$section}) ? $record->{$section} : [];
                $page[$section] = $this->mergeKnownShape($defaults[$section], $persisted);
            }

            $page['tabs'] = $this->normalizeFixedCollection(
                $defaults['tabs'],
                is_array($record->tabs) ? $record->tabs : []
            );
            $page['info_cards'] = $this->normalizeFixedSection(
                $defaults['info_cards'],
                is_array($record->info_cards) ? $record->info_cards : [],
                'cards'
            );
            $page['delivery_steps'] = $this->normalizeFixedSection(
                $defaults['delivery_steps'],
                is_array($record->delivery_steps) ? $record->delivery_steps : [],
                'steps'
            );
            $page['faqs'] = $this->normalizeFixedSection(
                $defaults['faqs'],
                is_array($record->faqs) ? $record->faqs : [],
                'items'
            );
            $page['address_checklist'] = $this->normalizeFixedSection(
                $defaults['address_checklist'],
                is_array($record->address_checklist) ? $record->address_checklist : [],
                'items'
            );
        }

        $page['notice']['fallback_icon'] = $defaults['notice']['fallback_icon'];

        return $this->resolveMedia($this->normalizeDestinations($page, $defaults));
    }

    /** @param array<int, array<string, mixed>> $defaults @param array<int, mixed> $persisted @return array<int, array<string, mixed>> */
    private function normalizeFixedCollection(array $defaults, array $persisted): array
    {
        $byId = [];
        foreach ($persisted as $item) {
            if (! is_array($item) || ! isset($item['id'])) {
                continue;
            }
            $id = (string) $item['id'];
            if ($id === '' || array_key_exists($id, $byId)) {
                continue;
            }
            $byId[$id] = $item;
        }

        return array_map(function (array $defaultItem) use ($byId): array {
            $id = (string) $defaultItem['id'];
            $merged = $this->mergeKnownShape($defaultItem, $byId[$id] ?? []);
            $merged['id'] = $id;

            return $merged;
        }, $defaults);
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
            $merged = $this->mergeKnownShape($defaultItem, $byId[$id] ?? []);
            $merged['id'] = $defaultItem['id'];
            if (array_key_exists('fallback_icon', $defaultItem)) {
                $merged['fallback_icon'] = $defaultItem['fallback_icon'];
            }

            return $merged;
        }, $defaults[$collectionKey]);

        return $result;
    }

    /** @param array<string, mixed> $page @param array<string, mixed> $defaults @return array<string, mixed> */
    private function normalizeDestinations(array $page, array $defaults): array
    {
        foreach (['cta.primary_url', 'cta.policy_url'] as $path) {
            $value = data_get($page, $path);
            if (! is_string($value) || ! PublicUrl::isAllowed($value)) {
                data_set($page, $path, data_get($defaults, $path));
            }
        }

        return $page;
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

    /** @param array<string, mixed> $page @return array<string, mixed> */
    private function resolveMedia(array $page): array
    {
        foreach ($page['info_cards']['cards'] as &$card) {
            $card['icon_url'] = $this->customUrl($card['icon_path'] ?? null);
            $card['has_custom_icon'] = $card['icon_url'] !== null;
        }
        unset($card);

        $page['notice']['icon_url'] = $this->customUrl($page['notice']['icon_path'] ?? null);
        $page['notice']['has_custom_icon'] = $page['notice']['icon_url'] !== null;

        foreach ($page['address_checklist']['items'] as &$item) {
            $item['icon_url'] = $this->customUrl($item['icon_path'] ?? null);
            $item['has_custom_icon'] = $item['icon_url'] !== null;
        }
        unset($item);

        return $page;
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
        if (! $this->isSafeShippingDeliveryPath($normalized)) {
            return null;
        }

        return Storage::disk('public')->exists($normalized) ? $normalized : null;
    }

    private function isSafeShippingDeliveryPath(string $path): bool
    {
        if (! str_starts_with($path, 'shipping-delivery-page/') || str_contains($path, "\0")) {
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
