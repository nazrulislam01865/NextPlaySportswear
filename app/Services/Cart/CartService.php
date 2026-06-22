<?php

namespace App\Services\Cart;

use App\Services\Storefront\ProductCatalogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CartService
{
    private const SESSION_ITEMS_KEY = 'nextplay_cart.items';
    private const SESSION_COUPON_KEY = 'nextplay_cart.coupon_code';

    public function __construct(
        private readonly ProductCatalogService $products,
    ) {
    }

    public function summary(bool $preview = false): array
    {
        $items = $preview ? $this->previewItems() : $this->items();
        $couponCode = $preview ? 'TEAM10' : (session(self::SESSION_COUPON_KEY) ?: null);

        $subtotal = collect($items)->sum('line_subtotal');
        $customizationTotal = collect($items)->sum('customization_total');
        $merchandiseTotal = $subtotal + $customizationTotal;
        $quantity = collect($items)->sum('quantity');
        $discount = $this->calculateDiscount($merchandiseTotal, $couponCode);
        $shipping = $this->calculateShipping($merchandiseTotal, $quantity);
        $tax = $this->calculateTax($merchandiseTotal - $discount);
        $total = max(0, $merchandiseTotal - $discount + $shipping + $tax);

        return [
            'items' => array_values($items),
            'coupon_code' => $couponCode,
            'subtotal' => round($subtotal, 2),
            'customization_total' => round($customizationTotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shipping, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'quantity' => (int) $quantity,
            'is_empty' => count($items) === 0,
            'is_preview' => $preview,
            'checkout_ready' => count($items) > 0,
            'trust_points' => $this->trustPoints(),
            'payment_badges' => ['Visa', 'Mastercard', 'PayPal', 'Stripe', 'Bank Transfer'],
        ];
    }

    public function items(): array
    {
        return collect(session(self::SESSION_ITEMS_KEY, []))
            ->map(fn (array $item): array => $this->repriceItem($item))
            ->filter()
            ->values()
            ->all();
    }

    public function count(): int
    {
        return (int) collect($this->items())->sum('quantity');
    }

    public function store(array $payload): array
    {
        $product = $this->products->findBySlug($payload['product_slug']);

        abort_if($product === null, 404);

        $customization = $this->sanitizeCustomization($payload, $product);
        $configuredQuantity = (int) collect($customization['configuration']['quantities'] ?? [])->sum();
        $quantity = $this->sanitizeQuantity($configuredQuantity > 0 ? $configuredQuantity : (int) ($payload['quantity'] ?? 1), $product);
        $this->validateRequiredConfiguration($product, $customization);
        $key = $this->makeItemKey($product['slug'], $customization);

        $items = $this->items();
        $existingIndex = collect($items)->search(fn (array $item): bool => $item['key'] === $key);

        if ($existingIndex !== false) {
            $items[$existingIndex]['quantity'] = $this->sanitizeQuantity($items[$existingIndex]['quantity'] + $quantity, $product);
            $items[$existingIndex]['updated_at'] = now()->toIso8601String();
        } else {
            $items[] = $this->repriceItem([
                'key' => $key,
                'product_slug' => $product['slug'],
                'quantity' => $quantity,
                'customization' => $customization,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        session()->put(self::SESSION_ITEMS_KEY, array_values($items));

        return $this->summary();
    }

    public function update(string $key, int $quantity): array
    {
        $items = collect($this->items())
            ->map(function (array $item) use ($key, $quantity): array {
                if (hash_equals($item['key'], $key)) {
                    $product = $this->products->findBySlug((string) $item['product_slug']);
                    $item['quantity'] = $product ? $this->sanitizeQuantity($quantity, $product) : $quantity;
                    $item['updated_at'] = now()->toIso8601String();
                }

                return $item;
            })
            ->values()
            ->all();

        session()->put(self::SESSION_ITEMS_KEY, $items);

        return $this->summary();
    }

    public function remove(string $key): array
    {
        $items = collect($this->items())
            ->reject(fn (array $item): bool => hash_equals($item['key'], $key))
            ->values()
            ->all();

        session()->put(self::SESSION_ITEMS_KEY, $items);

        return $this->summary();
    }

    public function applyCoupon(?string $code): array
    {
        $code = Str::upper(trim((string) $code));

        if ($code === '') {
            session()->forget(self::SESSION_COUPON_KEY);
        } elseif (array_key_exists($code, $this->availableCoupons())) {
            session()->put(self::SESSION_COUPON_KEY, $code);
        }

        return $this->summary();
    }

    public function removeCoupon(): array
    {
        session()->forget(self::SESSION_COUPON_KEY);

        return $this->summary();
    }

    private function repriceItem(array $item): array
    {
        $product = $this->products->findBySlug((string) ($item['product_slug'] ?? ''));

        if ($product === null) {
            return [];
        }

        $customization = $this->sanitizeCustomization((array) ($item['customization'] ?? []), $product);
        $configuredQuantity = (int) collect($customization['configuration']['quantities'] ?? [])->sum();
        $quantity = $this->sanitizeQuantity($configuredQuantity > 0 ? $configuredQuantity : (int) ($item['quantity'] ?? 1), $product);
        $unitPrice = $this->unitPriceForQuantity($product, $quantity);
        $customizationUnitPrice = $this->customizationUnitPrice($product, $customization, $quantity);

        return array_merge($item, [
            'key' => $item['key'] ?? $this->makeItemKey($product['slug'], $customization),
            'product' => Arr::only($product, ['slug', 'title', 'short_title', 'summary', 'sku', 'category', 'sport', 'image', 'alt', 'url', 'base_price', 'price']),
            'quantity' => $quantity,
            'customization' => $customization,
            'unit_price' => $unitPrice,
            'customization_unit_price' => $customizationUnitPrice,
            'line_subtotal' => round($unitPrice * $quantity, 2),
            'customization_total' => round($customizationUnitPrice * $quantity, 2),
            'line_total' => round(($unitPrice + $customizationUnitPrice) * $quantity, 2),
        ]);
    }

    private function sanitizeCustomization(array $payload, array $product): array
    {
        $designOption = Str::limit(trim((string) ($payload['design_option'] ?? 'Configured product')), 80, '');
        $deliveryPreference = Str::limit(trim((string) ($payload['delivery_preference'] ?? 'Standard production')), 80, '');
        $sizeSummary = Str::limit(trim((string) ($payload['size_summary'] ?? 'Sizes selected in configuration')), 600, '');
        $artworkStatus = Str::limit(trim((string) ($payload['artwork_status'] ?? 'Artwork can be sent now or later')), 120, '');
        $notes = Str::limit(trim((string) ($payload['notes'] ?? '')), 1000, '');
        $configuration = $this->normalizeConfiguration($payload['configuration_json'] ?? ($payload['configuration'] ?? []), $product);

        return [
            'design_option' => $designOption === '' ? 'Configured product' : $designOption,
            'delivery_preference' => $deliveryPreference === '' ? 'Standard production' : $deliveryPreference,
            'size_summary' => $sizeSummary === '' ? 'Sizes selected in configuration' : $sizeSummary,
            'artwork_status' => $artworkStatus === '' ? 'Artwork can be sent now or later' : $artworkStatus,
            'notes' => $notes,
            'configuration' => $configuration,
            'artwork_path' => isset($payload['artwork_path']) ? Str::limit((string) $payload['artwork_path'], 500, '') : null,
            'artwork_original_name' => isset($payload['artwork_original_name']) ? Str::limit((string) $payload['artwork_original_name'], 255, '') : null,
        ];
    }

    private function normalizeConfiguration(array|string $raw, array $product): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        $groups = collect($product['option_groups'] ?? [])->keyBy('id');
        $selections = [];
        foreach ((array) ($raw['selections'] ?? []) as $groupId => $valueId) {
            $group = $groups->get((string) $groupId);
            if (! $group || ! in_array($group['type'], ['image', 'swatch', 'buttons', 'select'], true)) {
                continue;
            }
            $allowed = collect($group['values'] ?? [])->pluck('id')->map(fn ($id) => (string) $id)->all();
            if (in_array((string) $valueId, $allowed, true)) {
                $selections[(string) $groupId] = (string) $valueId;
            }
        }

        $multiSelections = [];
        foreach ((array) ($raw['multi_selections'] ?? []) as $groupId => $valueIds) {
            $group = $groups->get((string) $groupId);
            if (! $group || $group['type'] !== 'checkbox') {
                continue;
            }
            $allowed = collect($group['values'] ?? [])->pluck('id')->map(fn ($id) => (string) $id)->all();
            $maximum = (int) ($group['maximum_selections'] ?? count($allowed));
            $multiSelections[(string) $groupId] = collect((array) $valueIds)
                ->map(fn ($id) => (string) $id)
                ->filter(fn ($id) => in_array($id, $allowed, true))
                ->unique()->take(max(1, $maximum))->values()->all();
        }

        $inputs = [];
        foreach ((array) ($raw['inputs'] ?? []) as $groupId => $value) {
            $group = $groups->get((string) $groupId);
            if (! $group || in_array($group['type'], ['image', 'swatch', 'buttons', 'select', 'checkbox', 'file'], true)) {
                continue;
            }
            $inputs[(string) $groupId] = Str::limit(trim((string) $value), $group['type'] === 'textarea' ? 2000 : 255, '');
        }

        $sizeLookup = collect($product['size_groups'] ?? [])->mapWithKeys(function (array $group): array {
            return collect($group['sizes'] ?? [])->mapWithKeys(fn (array $size) => [
                $group['id'].':'.$size['code'] => ['group' => $group['id'], 'size' => $size['code'], 'price_delta' => (float) ($size['price_delta'] ?? 0)],
            ])->all();
        });
        $quantities = [];
        foreach ((array) ($raw['quantities'] ?? []) as $key => $quantity) {
            if (! $sizeLookup->has((string) $key)) {
                continue;
            }
            $quantity = max(0, min(9999, (int) $quantity));
            if ($quantity > 0) {
                $quantities[(string) $key] = $quantity;
            }
        }

        $artworkMethods = collect($product['artwork_methods'] ?? [])->pluck('id')->map(fn ($id) => (string) $id)->all();
        $productionSpeeds = collect($product['production_speeds'] ?? [])->pluck('id')->map(fn ($id) => (string) $id)->all();

        return [
            'selections' => $selections,
            'multi_selections' => $multiSelections,
            'inputs' => $inputs,
            'quantities' => $quantities,
            'artwork_method' => in_array((string) ($raw['artwork_method'] ?? ''), $artworkMethods, true) ? (string) $raw['artwork_method'] : ($artworkMethods[0] ?? null),
            'production_speed' => in_array((string) ($raw['production_speed'] ?? ''), $productionSpeeds, true) ? (string) $raw['production_speed'] : ($productionSpeeds[0] ?? null),
        ];
    }

    private function validateRequiredConfiguration(array $product, array $customization): void
    {
        if (! ($product['is_customizable'] ?? false)) {
            return;
        }

        $configuration = $customization['configuration'] ?? [];
        foreach (($product['option_groups'] ?? []) as $group) {
            if (! ($group['required'] ?? false)) {
                continue;
            }

            $groupId = (string) $group['id'];
            $valid = match ($group['type']) {
                'checkbox' => count($configuration['multi_selections'][$groupId] ?? []) >= max(1, (int) ($group['minimum_selections'] ?? 1)),
                'image', 'swatch', 'buttons', 'select' => filled($configuration['selections'][$groupId] ?? null),
                'file' => filled($customization['artwork_path'] ?? null),
                default => filled($configuration['inputs'][$groupId] ?? null),
            };

            abort_unless($valid, 422, 'A required product customization is missing: '.$group['label']);
        }

        $artwork = collect($product['artwork_methods'] ?? [])->firstWhere('id', $configuration['artwork_method'] ?? null);
        if (($artwork['requires_upload'] ?? false) && ! filled($customization['artwork_path'] ?? null)) {
            abort(422, 'The selected artwork method requires an uploaded file.');
        }
    }

    private function sanitizeQuantity(int $quantity, array $product): int
    {
        $minimum = max(1, (int) ($product['minimum_quantity'] ?? 1));
        $maximum = min(999, max($minimum, (int) ($product['maximum_quantity'] ?? 999)));

        if (($product['track_inventory'] ?? false) && ! ($product['allow_backorder'] ?? false)) {
            $maximum = min($maximum, max(0, (int) ($product['stock_quantity'] ?? 0)));
        }

        abort_if($maximum < $minimum, 422, 'This product is currently unavailable in the required minimum quantity.');

        return min(max($quantity, $minimum), $maximum);
    }

    private function makeItemKey(string $slug, array $customization): string
    {
        return sha1($slug.'|'.json_encode($customization, JSON_THROW_ON_ERROR));
    }

    private function unitPriceForQuantity(array $product, int $quantity): float
    {
        $tier = collect($product['price_tiers'] ?? [])->first(function (array $tier) use ($quantity): bool {
            return $quantity >= (int) ($tier['min'] ?? 1)
                && (($tier['max'] ?? null) === null || $quantity <= (int) $tier['max']);
        });

        return round((float) ($tier['unit'] ?? $product['base_price'] ?? 0), 2);
    }

    private function customizationUnitPrice(array $product, array $customization, int $quantity): float
    {
        $configuration = $customization['configuration'] ?? [];
        $groups = collect($product['option_groups'] ?? [])->keyBy('id');
        $delta = 0.0;

        foreach ((array) ($configuration['selections'] ?? []) as $groupId => $valueId) {
            $value = collect($groups->get($groupId)['values'] ?? [])->firstWhere('id', $valueId);
            $delta += (float) ($value['price_delta'] ?? 0);
        }

        foreach ((array) ($configuration['multi_selections'] ?? []) as $groupId => $valueIds) {
            $values = collect($groups->get($groupId)['values'] ?? [])->keyBy('id');
            foreach ((array) $valueIds as $valueId) {
                $delta += (float) ($values->get($valueId)['price_delta'] ?? 0);
            }
        }

        $artwork = collect($product['artwork_methods'] ?? [])->firstWhere('id', $configuration['artwork_method'] ?? null);
        $speed = collect($product['production_speeds'] ?? [])->firstWhere('id', $configuration['production_speed'] ?? null);
        $delta += (float) ($artwork['price_delta'] ?? 0) + (float) ($speed['price_delta'] ?? 0);

        if ($quantity > 0) {
            $sizeLookup = collect($product['size_groups'] ?? [])->flatMap(fn (array $group) => collect($group['sizes'] ?? [])->mapWithKeys(fn (array $size) => [
                $group['id'].':'.$size['code'] => (float) ($size['price_delta'] ?? 0),
            ]));
            $weighted = 0.0;
            foreach ((array) ($configuration['quantities'] ?? []) as $key => $count) {
                $weighted += (float) ($sizeLookup->get($key, 0)) * (int) $count;
            }
            $delta += $weighted / $quantity;
        }

        return round($delta, 2);
    }

    private function calculateDiscount(float $merchandiseTotal, ?string $couponCode): float
    {
        if ($couponCode === null) {
            return 0.00;
        }

        $coupon = $this->availableCoupons()[Str::upper($couponCode)] ?? null;

        if ($coupon === null || $merchandiseTotal < $coupon['minimum']) {
            return 0.00;
        }

        if ($coupon['type'] === 'percentage') {
            return min(round($merchandiseTotal * $coupon['value'], 2), $coupon['max_discount']);
        }

        return min($merchandiseTotal, $coupon['value']);
    }

    private function calculateShipping(float $merchandiseTotal, int $quantity): float
    {
        if ($quantity === 0) {
            return 0.00;
        }

        if ($merchandiseTotal >= 450) {
            return 0.00;
        }

        return round(12 + max(0, $quantity - 1) * 2.25, 2);
    }

    private function calculateTax(float $taxableAmount): float
    {
        return round(max(0, $taxableAmount) * 0.00, 2);
    }

    private function availableCoupons(): array
    {
        return [
            'TEAM10' => ['type' => 'percentage', 'value' => 0.10, 'minimum' => 100, 'max_discount' => 75],
            'NEXTPLAY25' => ['type' => 'fixed', 'value' => 25, 'minimum' => 250, 'max_discount' => 25],
        ];
    }

    private function previewItems(): array
    {
        $preview = [
            [
                'product_slug' => 'custom-cool-shapes-adult-youth-unisex-football-jersey',
                'quantity' => 18,
                'customization' => [
                    'design_option' => 'Modern Graphic',
                    'delivery_preference' => 'Standard production',
                    'size_summary' => 'Men: M × 5, L × 7, XL × 4; Youth: YL × 2',
                    'artwork_status' => 'Logo file ready; roster will be uploaded during proof review',
                    'notes' => 'Navy/red/white color direction. Add player names and numbers on back.',
                ],
            ],
            [
                'product_slug' => 'custom-embroidered-cap',
                'quantity' => 24,
                'customization' => [
                    'design_option' => 'Default Team Style',
                    'delivery_preference' => 'Standard production',
                    'size_summary' => 'One size adjustable × 24',
                    'artwork_status' => 'Front logo embroidery file needed',
                    'notes' => 'Use same logo as jersey order. Match navy cap with red stitching accent.',
                ],
            ],
        ];

        return collect($preview)
            ->map(function (array $item): array {
                $customization = $this->sanitizeCustomization($item['customization']);

                return $this->repriceItem([
                    'key' => $this->makeItemKey($item['product_slug'], $customization),
                    'product_slug' => $item['product_slug'],
                    'quantity' => $item['quantity'],
                    'customization' => $customization,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
            })
            ->values()
            ->all();
    }

    private function trustPoints(): array
    {
        return [
            ['title' => 'Free design preview', 'description' => 'Review the layout before production begins.'],
            ['title' => 'Unlimited proof revisions', 'description' => 'Adjust colors, spelling, placement, and roster details.'],
            ['title' => 'Secure checkout', 'description' => 'Final totals are recalculated on the Laravel backend.'],
            ['title' => 'Bulk order support', 'description' => 'Team quantities and quote requests stay organized.'],
        ];
    }
}
