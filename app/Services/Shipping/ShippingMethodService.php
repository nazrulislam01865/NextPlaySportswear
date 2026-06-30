<?php

namespace App\Services\Shipping;

use App\Models\Product;
use App\Models\ProductShippingMethod;
use App\Models\ShippingMethod;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ShippingMethodService
{
    public function availableMethods(array $cart, array $shippingAddress = [], ?array $ruralSurcharge = null): array
    {
        $subtotal = (float) ($cart['merchandise_total'] ?? (($cart['subtotal'] ?? 0) + ($cart['customization_total'] ?? 0)));
        $discount = (float) ($cart['discount'] ?? 0);
        $quantity = max(0, (int) ($cart['quantity'] ?? 0));
        $country = trim((string) ($shippingAddress['country'] ?? ''));
        $state = trim((string) ($shippingAddress['state'] ?? ''));
        $ruralAmount = (float) ($ruralSurcharge['amount'] ?? 0);
        $productionWindow = $this->productionWindow($cart);

        return $this->methodRecords()
            ->filter(function (ShippingMethod $method) use ($subtotal, $quantity, $country, $state): bool {
                if ($method->minimum_quantity !== null && $quantity < (int) $method->minimum_quantity) {
                    return false;
                }

                if ($method->maximum_quantity !== null && $quantity > (int) $method->maximum_quantity) {
                    return false;
                }

                if ($method->minimum_subtotal !== null && $subtotal < (float) $method->minimum_subtotal) {
                    return false;
                }

                if ($method->maximum_subtotal !== null && $subtotal > (float) $method->maximum_subtotal) {
                    return false;
                }

                if (filled($method->country) && filled($country) && strcasecmp((string) $method->country, $country) !== 0) {
                    return false;
                }

                if (filled($method->state) && filled($state) && strcasecmp((string) $method->state, $state) !== 0) {
                    return false;
                }

                return true;
            })
            ->map(function (ShippingMethod $method) use ($subtotal, $discount, $quantity, $ruralSurcharge, $ruralAmount, $productionWindow): array {
                $base = (float) $method->base_price + ((float) $method->per_item_price * max(0, $quantity - 1));
                $freeMinimum = $method->free_shipping_minimum === null ? null : (float) $method->free_shipping_minimum;

                if ($freeMinimum !== null && ($subtotal - $discount) >= $freeMinimum) {
                    $base = 0.00;
                }

                $quoteBased = (bool) $method->is_quote_based;
                $price = round(($quoteBased ? 0.00 : $base) + $ruralAmount, 2);
                $transitMin = max(0, (int) $method->minimum_days);
                $transitMax = max($transitMin, (int) $method->maximum_days);
                $totalMin = (int) ($productionWindow['minimum_days'] ?? 0) + $transitMin;
                $totalMax = (int) ($productionWindow['maximum_days'] ?? 0) + $transitMax;

                return [
                    'id' => $method->id,
                    'code' => (string) $method->code,
                    'title' => (string) $method->name,
                    'description' => (string) ($method->description ?? ''),
                    'eta' => $method->starts_after_artwork_approval
                        ? 'After artwork approval: '.$totalMin.'–'.$totalMax.' business days'
                        : 'Estimated delivery: '.$totalMin.'–'.$totalMax.' business days',
                    'base_price' => round($quoteBased ? 0.00 : $base, 2),
                    'price' => $price,
                    'quote_based' => $quoteBased,
                    'is_default' => (bool) $method->is_default,
                    'rural_surcharge' => $ruralSurcharge,
                    'display_price' => $quoteBased
                        ? ($ruralAmount > 0 ? 'Quote + $'.number_format($ruralAmount, 2) : 'Quote')
                        : '$'.number_format($price, 2),
                    'minimum_days' => $method->minimum_days,
                    'maximum_days' => $method->maximum_days,
                    'starts_after_artwork_approval' => (bool) $method->starts_after_artwork_approval,
                    'delivery_estimate' => [
                        'start_event' => $method->starts_after_artwork_approval ? 'artwork_confirmation' : 'order_placement',
                        'production_minimum_days' => (int) ($productionWindow['minimum_days'] ?? 0),
                        'production_maximum_days' => (int) ($productionWindow['maximum_days'] ?? 0),
                        'transit_minimum_days' => $transitMin,
                        'transit_maximum_days' => $transitMax,
                        'total_minimum_business_days' => $totalMin,
                        'total_maximum_business_days' => $totalMax,
                        'product_lines' => $productionWindow['lines'] ?? [],
                    ],
                ];
            })
            ->values()
            ->all();
    }

    public function productShippingTotal(array $cart): array
    {
        $lines = [];
        $total = 0.00;

        foreach ((array) ($cart['items'] ?? []) as $item) {
            $slug = (string) ($item['product_slug'] ?? Arr::get($item, 'product.slug', ''));
            $selectedCode = (string) Arr::get($item, 'customization.configuration.shipping_method', '');
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            if ($slug === '' || $selectedCode === '') {
                continue;
            }

            $product = Product::query()->where('slug', $slug)->with('shippingMethods')->first();

            if (! $product instanceof Product || ! $product->shipping_methods_enabled) {
                continue;
            }

            /** @var ProductShippingMethod|null $method */
            $method = $product->shippingMethods
                ->where('is_active', true)
                ->firstWhere('code', $selectedCode);

            if (! $method instanceof ProductShippingMethod) {
                continue;
            }

            $amount = match ($method->charge_type) {
                'fixed_order' => (float) $method->price_adjustment,
                'included' => 0.00,
                default => (float) $method->price_adjustment * $quantity,
            };

            $amount = round(max(0, $amount), 2);
            $total += $amount;
            $lines[] = [
                'product' => Arr::get($item, 'product.short_title', Arr::get($item, 'product.title', 'Product')),
                'method' => $method->name,
                'code' => $method->code,
                'charge_type' => $method->charge_type,
                'quantity' => $quantity,
                'amount' => $amount,
                'minimum_days' => (int) $method->minimum_days,
                'maximum_days' => (int) $method->maximum_days,
            ];
        }

        return [
            'total' => round($total, 2),
            'lines' => $lines,
        ];
    }

    public function productionWindow(array $cart): array
    {
        $lines = [];
        $minimumDays = 0;
        $maximumDays = 0;

        foreach ((array) ($cart['items'] ?? []) as $item) {
            $slug = (string) ($item['product_slug'] ?? Arr::get($item, 'product.slug', ''));
            $speedCode = (string) Arr::get($item, 'customization.configuration.production_speed', '');

            if ($slug === '') {
                continue;
            }

            $product = Product::query()->where('slug', $slug)->with('productionSpeeds')->first();
            $speed = $product?->productionSpeeds?->where('is_active', true)->firstWhere('code', $speedCode)
                ?? $product?->productionSpeeds?->where('is_active', true)->sortBy('sort_order')->first();

            $min = max(0, (int) ($speed?->minimum_days ?? 0));
            $max = max($min, (int) ($speed?->maximum_days ?? $min));

            $minimumDays = max($minimumDays, $min);
            $maximumDays = max($maximumDays, $max);

            if ($product instanceof Product && ($min > 0 || $max > 0)) {
                $lines[] = [
                    'product' => Arr::get($item, 'product.short_title', $product->name),
                    'production_option' => $speed?->name ?: 'Standard production',
                    'minimum_days' => $min,
                    'maximum_days' => $max,
                ];
            }
        }

        return [
            'minimum_days' => $minimumDays,
            'maximum_days' => $maximumDays,
            'lines' => $lines,
        ];
    }

    public function defaultMethod(array $methods): ?array
    {
        return collect($methods)->firstWhere('is_default', true) ?? (collect($methods)->first() ?: null);
    }

    private function methodRecords(): Collection
    {
        if (! Schema::hasTable('shipping_methods')) {
            return collect($this->fallbackMethods());
        }

        return ShippingMethod::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function fallbackMethods(): array
    {
        return [
            new ShippingMethod([
                'name' => 'Standard Shipping',
                'code' => 'standard',
                'description' => 'Best for regular orders.',
                'base_price' => 12.00,
                'per_item_price' => 2.25,
                'free_shipping_minimum' => 450.00,
                'minimum_days' => 5,
                'maximum_days' => 7,
                'starts_after_artwork_approval' => true,
                'is_default' => true,
                'is_active' => true,
            ]),
        ];
    }
}
