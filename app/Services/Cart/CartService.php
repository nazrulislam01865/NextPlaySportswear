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

        $quantity = $this->sanitizeQuantity((int) ($payload['quantity'] ?? 1));
        $customization = $this->sanitizeCustomization($payload);
        $key = $this->makeItemKey($product['slug'], $customization);

        $items = $this->items();
        $existingIndex = collect($items)->search(fn (array $item): bool => $item['key'] === $key);

        if ($existingIndex !== false) {
            $items[$existingIndex]['quantity'] = $this->sanitizeQuantity($items[$existingIndex]['quantity'] + $quantity);
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
                    $item['quantity'] = $this->sanitizeQuantity($quantity);
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

        $quantity = $this->sanitizeQuantity((int) ($item['quantity'] ?? 1));
        $customization = $this->sanitizeCustomization((array) ($item['customization'] ?? []));
        $unitPrice = round((float) ($product['base_price'] ?? 0), 2);
        $customizationUnitPrice = $this->customizationUnitPrice($customization);

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

    private function sanitizeCustomization(array $payload): array
    {
        $designOption = Str::limit(trim((string) ($payload['design_option'] ?? 'Default Team Style')), 80, '');
        $deliveryPreference = Str::limit(trim((string) ($payload['delivery_preference'] ?? 'Standard production')), 80, '');
        $sizeSummary = Str::limit(trim((string) ($payload['size_summary'] ?? 'Sizes can be confirmed during proof review')), 600, '');
        $artworkStatus = Str::limit(trim((string) ($payload['artwork_status'] ?? 'Artwork/logo can be sent now or later')), 120, '');
        $notes = Str::limit(trim((string) ($payload['notes'] ?? '')), 1000, '');

        return [
            'design_option' => $designOption === '' ? 'Default Team Style' : $designOption,
            'delivery_preference' => $deliveryPreference === '' ? 'Standard production' : $deliveryPreference,
            'size_summary' => $sizeSummary === '' ? 'Sizes can be confirmed during proof review' : $sizeSummary,
            'artwork_status' => $artworkStatus === '' ? 'Artwork/logo can be sent now or later' : $artworkStatus,
            'notes' => $notes,
        ];
    }

    private function sanitizeQuantity(int $quantity): int
    {
        return min(max($quantity, 1), 999);
    }

    private function makeItemKey(string $slug, array $customization): string
    {
        return sha1($slug . '|' . json_encode($customization, JSON_THROW_ON_ERROR));
    }

    private function customizationUnitPrice(array $customization): float
    {
        return match (Str::lower((string) ($customization['design_option'] ?? ''))) {
            'modern graphic', 'modern-graphic' => 3.00,
            default => 0.00,
        };
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
