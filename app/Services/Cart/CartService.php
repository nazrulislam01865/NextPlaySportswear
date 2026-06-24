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
        $genericShippingItems = collect($items)->where('uses_product_shipping', false);
        $shipping = $this->calculateShipping(
            (float) $genericShippingItems->sum(fn (array $item) => ($item['line_subtotal'] ?? 0) + ($item['customization_total'] ?? 0)),
            (int) $genericShippingItems->sum('quantity')
        );
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

        $customization = $this->sanitizeCustomization($payload, $product, (int) ($payload['quantity'] ?? 1));
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

    public function clear(): void
    {
        session()->forget([self::SESSION_ITEMS_KEY, self::SESSION_COUPON_KEY]);
    }

    private function repriceItem(array $item): array
    {
        $product = $this->products->findBySlug((string) ($item['product_slug'] ?? ''));

        if ($product === null) {
            return [];
        }

        $customization = $this->sanitizeCustomization(
            (array) ($item['customization'] ?? []),
            $product,
            (int) ($item['quantity'] ?? 1)
        );
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
            'uses_product_shipping' => ! empty($product['shipping_methods']),
        ]);
    }

    private function sanitizeCustomization(array $payload, array $product, int $fallbackQuantity = 1): array
    {
        $designOption = Str::limit(trim((string) ($payload['design_option'] ?? 'Configured product')), 80, '');
        $deliveryPreference = Str::limit(trim((string) ($payload['delivery_preference'] ?? 'Standard production')), 80, '');
        $sizeSummary = Str::limit(trim((string) ($payload['size_summary'] ?? 'Sizes selected in configuration')), 600, '');
        $artworkStatus = Str::limit(trim((string) ($payload['artwork_status'] ?? 'Artwork can be sent now or later')), 120, '');
        $notes = Str::limit(trim((string) ($payload['notes'] ?? '')), 1000, '');
        $configuration = $this->normalizeConfiguration(
            $payload['configuration_json'] ?? ($payload['configuration'] ?? []),
            $product,
            $fallbackQuantity
        );
        $selectedSpeed = collect($product['production_speeds'] ?? [])->firstWhere('id', $configuration['production_speed'] ?? null);
        $selectedShipping = collect($product['shipping_methods'] ?? [])->firstWhere('id', $configuration['shipping_method'] ?? null);
        $secureDeliveryLabels = collect([$selectedSpeed['label'] ?? null, $selectedShipping['label'] ?? null])->filter()->implode(' / ');

        $artworkFiles = collect((array) ($payload['artwork_files'] ?? []))
            ->filter(fn ($file) => is_array($file) && filled($file['path'] ?? null))
            ->map(fn ($file) => [
                'path' => Str::limit((string) $file['path'], 500, ''),
                'original_name' => Str::limit((string) ($file['original_name'] ?? 'Artwork file'), 255, ''),
                'size' => max(0, (int) ($file['size'] ?? 0)),
                'mime_type' => Str::limit((string) ($file['mime_type'] ?? 'application/octet-stream'), 120, ''),
            ])
            ->take(12)
            ->values();

        if ($artworkFiles->isEmpty() && filled($payload['artwork_path'] ?? null)) {
            $artworkFiles->push([
                'path' => Str::limit((string) $payload['artwork_path'], 500, ''),
                'original_name' => Str::limit((string) ($payload['artwork_original_name'] ?? 'Artwork file'), 255, ''),
                'size' => 0,
                'mime_type' => 'application/octet-stream',
            ]);
        }

        $firstArtwork = $artworkFiles->first();

        return [
            'design_option' => $designOption === '' ? 'Configured product' : $designOption,
            'delivery_preference' => $secureDeliveryLabels ?: ($deliveryPreference === '' ? 'Standard production' : $deliveryPreference),
            'size_summary' => $sizeSummary === '' ? 'Sizes selected in configuration' : $sizeSummary,
            'artwork_status' => $artworkFiles->isNotEmpty()
                ? $artworkFiles->count().' artwork file'.($artworkFiles->count() === 1 ? '' : 's').' uploaded'
                : ($artworkStatus === '' ? 'No artwork uploaded' : $artworkStatus),
            'notes' => $notes,
            'configuration' => $configuration,
            'artwork_files' => $artworkFiles->all(),
            // Legacy first-file fields remain for existing order/cart views.
            'artwork_path' => $firstArtwork['path'] ?? null,
            'artwork_original_name' => $firstArtwork['original_name'] ?? null,
        ];
    }

    private function normalizeConfiguration(array|string $raw, array $product, int $fallbackQuantity = 1): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        $groups = collect($product['option_groups'] ?? [])->keyBy('id');
        $selections = [];
        $multiSelections = [];
        $inputs = [];

        foreach ($groups as $groupId => $group) {
            $mode = (string) ($group['display_mode'] ?? 'customer');
            $type = (string) ($group['type'] ?? 'select');
            $values = collect($group['values'] ?? []);

            if ($mode === 'fixed') {
                if ($type === 'checkbox') {
                    $fixed = $values->where('default', true)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
                    if ($fixed !== []) {
                        $multiSelections[(string) $groupId] = $fixed;
                    }
                } elseif (in_array($type, ['image', 'swatch', 'buttons', 'select'], true)) {
                    $fixedCode = (string) ($group['fixed_value_code'] ?? '');
                    $fixedValue = $values->firstWhere('id', $fixedCode) ?? $values->firstWhere('default', true) ?? $values->first();
                    if ($fixedValue) {
                        $selections[(string) $groupId] = (string) $fixedValue['id'];
                    }
                } elseif ($type !== 'file' && filled($group['fixed_text_value'] ?? null)) {
                    $inputs[(string) $groupId] = Str::limit(trim((string) $group['fixed_text_value']), $type === 'textarea' ? 2000 : 255, '');
                }
                continue;
            }

            if ($mode !== 'customer') {
                continue;
            }

            if (in_array($type, ['image', 'swatch', 'buttons', 'select'], true)) {
                $valueId = (string) (($raw['selections'] ?? [])[$groupId] ?? '');
                $allowed = $values->pluck('id')->map(fn ($id) => (string) $id)->all();
                if (in_array($valueId, $allowed, true)) {
                    $selections[(string) $groupId] = $valueId;
                }
                continue;
            }

            if ($type === 'checkbox') {
                $allowed = $values->pluck('id')->map(fn ($id) => (string) $id)->all();
                $maximum = (int) ($group['maximum_selections'] ?? count($allowed));
                $multiSelections[(string) $groupId] = collect((array) (($raw['multi_selections'] ?? [])[$groupId] ?? []))
                    ->map(fn ($id) => (string) $id)
                    ->filter(fn ($id) => in_array($id, $allowed, true))
                    ->unique()->take(max(1, $maximum))->values()->all();
                continue;
            }

            if ($type !== 'file') {
                $value = (($raw['inputs'] ?? [])[$groupId] ?? null);
                if ($value !== null) {
                    $inputs[(string) $groupId] = Str::limit(trim((string) $value), $type === 'textarea' ? 2000 : 255, '');
                }
            }
        }

        $sizeLookup = collect($product['size_groups'] ?? [])->mapWithKeys(function (array $group): array {
            return collect($group['sizes'] ?? [])->mapWithKeys(fn (array $size) => [
                $group['id'].':'.$size['code'] => [
                    'group' => $group['id'], 'group_label' => $group['label'],
                    'size' => $size['code'], 'size_label' => $size['label'],
                    'price_delta' => 0.0,
                ],
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

        $productionSpeeds = collect($product['production_speeds'] ?? []);
        $productionQuantity = max(
            (int) collect($quantities)->sum() ?: max(1, $fallbackQuantity),
            (int) ($product['minimum_quantity'] ?? 1)
        );
        $availableProductionSpeeds = $productionSpeeds->filter(function (array $speed) use ($productionQuantity): bool {
            $minimum = max(1, (int) ($speed['minimum_quantity'] ?? 1));
            $maximum = filled($speed['maximum_quantity'] ?? null)
                ? (int) $speed['maximum_quantity']
                : null;

            return $productionQuantity >= $minimum
                && ($maximum === null || $productionQuantity <= $maximum);
        })->values();
        $requestedProductionSpeed = (string) ($raw['production_speed'] ?? '');
        $productionSpeed = $availableProductionSpeeds->firstWhere('id', $requestedProductionSpeed)
            ?? $availableProductionSpeeds->first();
        $shippingMethods = collect($product['shipping_methods'] ?? []);
        $shippingIds = $shippingMethods->pluck('id')->map(fn ($id) => (string) $id)->all();
        $defaultShipping = $shippingMethods->firstWhere('default', true)['id'] ?? ($shippingIds[0] ?? null);

        $rosterSettings = $product['jersey_roster'] ?? [];
        $rosterAvailable = ($product['product_profile'] ?? 'standard') === 'jersey' && (bool) ($rosterSettings['enabled'] ?? false);
        $rosterEnabled = $rosterAvailable && (! (bool) ($rosterSettings['optional'] ?? true) || filter_var($raw['roster_enabled'] ?? false, FILTER_VALIDATE_BOOL));
        $roster = [];

        if ($rosterEnabled) {
            $desiredRows = [];
            foreach ($quantities as $sizeKey => $count) {
                $size = $sizeLookup->get($sizeKey);
                for ($index = 0; $index < $count; $index++) {
                    $desiredRows[] = [
                        'size_key' => $sizeKey,
                        'size_group' => $size['group'],
                        'size_group_label' => $size['group_label'],
                        'size_code' => $size['size'],
                        'size_label' => $size['size_label'],
                    ];
                }
            }

            abort_if(count($desiredRows) > 250, 422, 'Per-jersey personalization is limited to 250 pieces per configured cart line.');

            $fields = collect($rosterSettings['fields'] ?? [])->filter(fn ($field) => (bool) ($field['enabled'] ?? true))->keyBy('key');
            $submittedRows = array_values((array) ($raw['roster'] ?? []));
            foreach ($desiredRows as $index => $row) {
                $submittedValues = (array) ($submittedRows[$index]['values'] ?? []);
                $values = [];
                foreach ($fields as $key => $field) {
                    $maximum = max(1, min(120, (int) ($field['max_length'] ?? 60)));
                    $value = Str::limit(trim((string) ($submittedValues[$key] ?? '')), $maximum, '');
                    if (($field['type'] ?? 'text') === 'number') {
                        $value = preg_replace('/[^0-9A-Za-z\-]/', '', $value) ?? '';
                    }
                    $values[(string) $key] = $value;
                }
                $roster[] = $row + ['values' => $values];
            }
        }

        return [
            'selections' => $selections,
            'multi_selections' => $multiSelections,
            'inputs' => $inputs,
            'quantities' => $quantities,
            'production_speed' => filled($productionSpeed['id'] ?? null) ? (string) $productionSpeed['id'] : null,
            'shipping_method' => in_array((string) ($raw['shipping_method'] ?? ''), $shippingIds, true) ? (string) $raw['shipping_method'] : $defaultShipping,
            'roster_enabled' => $rosterEnabled,
            'roster' => $roster,
        ];
    }

    private function validateRequiredConfiguration(array $product, array $customization): void
    {
        if (! ($product['is_customizable'] ?? false)) {
            return;
        }

        $configuration = $customization['configuration'] ?? [];
        foreach (($product['option_groups'] ?? []) as $group) {
            if (($group['display_mode'] ?? 'customer') !== 'customer' || ! ($group['required'] ?? false)) {
                continue;
            }

            $groupId = (string) $group['id'];
            $valid = match ($group['type']) {
                'checkbox' => count($configuration['multi_selections'][$groupId] ?? []) >= max(1, (int) ($group['minimum_selections'] ?? 1)),
                'image', 'swatch', 'buttons', 'select' => filled($configuration['selections'][$groupId] ?? null),
                'file' => count($customization['artwork_files'] ?? []) > 0,
                default => filled($configuration['inputs'][$groupId] ?? null),
            };

            abort_unless($valid, 422, 'A required product customization is missing: '.$group['label']);
        }

        $artworkSettings = $product['artwork_upload'] ?? ['enabled' => false];
        $artworkFiles = collect($customization['artwork_files'] ?? []);
        if (($artworkSettings['enabled'] ?? false) && ($artworkSettings['required'] ?? false)) {
            abort_unless($artworkFiles->isNotEmpty(), 422, 'Upload at least one custom artwork file.');
        }
        if ($artworkFiles->count() > max(1, min(12, (int) ($artworkSettings['max_files'] ?? 5)))) {
            abort(422, 'Too many custom artwork files were uploaded.');
        }

        $rosterSettings = $product['jersey_roster'] ?? [];
        if (($product['product_profile'] ?? 'standard') === 'jersey' && ($rosterSettings['enabled'] ?? false)) {
            if (! ($rosterSettings['optional'] ?? true)) {
                abort_unless((bool) ($configuration['roster_enabled'] ?? false), 422, 'Player details are required for this jersey.');
            }

            if ($configuration['roster_enabled'] ?? false) {
                $requiredFields = collect($rosterSettings['fields'] ?? [])->filter(fn ($field) => ($field['enabled'] ?? true) && ($field['required'] ?? false));
                foreach (($configuration['roster'] ?? []) as $index => $row) {
                    foreach ($requiredFields as $field) {
                        abort_unless(filled($row['values'][$field['key']] ?? null), 422, 'Complete '.$field['label'].' for jersey '.($index + 1).'.');
                    }
                }
            }
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
        $perUnit = 0.0;
        $fixedOrder = 0.0;

        $applyValue = function (?array $value) use (&$perUnit, &$fixedOrder): void {
            $amount = (float) ($value['price_delta'] ?? 0);
            $chargeType = (string) ($value['charge_type'] ?? 'per_unit');
            if ($chargeType === 'fixed_order') {
                $fixedOrder += $amount;
            } elseif ($chargeType !== 'included') {
                $perUnit += $amount;
            }
        };

        foreach ((array) ($configuration['selections'] ?? []) as $groupId => $valueId) {
            $applyValue(collect($groups->get($groupId)['values'] ?? [])->firstWhere('id', $valueId));
        }

        foreach ((array) ($configuration['multi_selections'] ?? []) as $groupId => $valueIds) {
            $values = collect($groups->get($groupId)['values'] ?? [])->keyBy('id');
            foreach ((array) $valueIds as $valueId) {
                $applyValue($values->get($valueId));
            }
        }

        $speed = collect($product['production_speeds'] ?? [])
            ->filter(function (array $speed) use ($quantity): bool {
                $minimum = max(1, (int) ($speed['minimum_quantity'] ?? 1));
                $maximum = filled($speed['maximum_quantity'] ?? null) ? (int) $speed['maximum_quantity'] : null;

                return $quantity >= $minimum && ($maximum === null || $quantity <= $maximum);
            })
            ->firstWhere('id', $configuration['production_speed'] ?? null);
        $perUnit += (float) ($speed['price_delta'] ?? 0);

        $shipping = collect($product['shipping_methods'] ?? [])->firstWhere('id', $configuration['shipping_method'] ?? null);
        if ($shipping) {
            $amount = (float) ($shipping['price_delta'] ?? 0);
            if (($shipping['charge_type'] ?? 'per_unit') === 'fixed_order') {
                $fixedOrder += $amount;
            } elseif (($shipping['charge_type'] ?? 'per_unit') !== 'included') {
                $perUnit += $amount;
            }
        }

        if ($quantity > 0) {
            // Sizes determine only the total quantity. The price tier chosen for that
            // total quantity determines the base unit price; sizes never add a price.
            $perUnit += $fixedOrder / $quantity;
        }

        return round($perUnit, 2);
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
                $product = $this->products->findBySlug($item['product_slug']);
                if ($product === null) {
                    return [];
                }

                $customization = $this->sanitizeCustomization(
                    $item['customization'],
                    $product,
                    (int) $item['quantity']
                );

                return $this->repriceItem([
                    'key' => $this->makeItemKey($item['product_slug'], $customization),
                    'product_slug' => $item['product_slug'],
                    'quantity' => $item['quantity'],
                    'customization' => $customization,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
            })
            ->filter()
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
