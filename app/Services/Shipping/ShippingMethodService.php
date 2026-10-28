<?php

namespace App\Services\Shipping;

use App\Models\Product;
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
        $ruralAmount = (float) ($ruralSurcharge['amount'] ?? 0);
        $productionWindow = $this->productionWindow($cart);
        $lineCount = max(1, count((array) ($cart['items'] ?? [])));

        $holidayService = app(\App\Services\Shipping\HolidayCalendarService::class);
        $country = (string) ($shippingAddress['country'] ?? $shippingAddress['country_code'] ?? 'United Kingdom');

        return $this->methodRecords()
            ->map(function (ShippingMethod $method) use ($subtotal, $discount, $quantity, $lineCount, $ruralSurcharge, $ruralAmount, $productionWindow, $holidayService, $country): array {
                $base = $this->methodBaseCharge($method, $quantity, $lineCount);
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

                $holidayWindow = $holidayService->calculateDeliveryWindow(now(), $totalMin, $totalMax, $country);
                $isHolidayAdjusted = (bool) ($holidayWindow['is_holiday_adjusted'] ?? false);
                $holidayNotice = $isHolidayAdjusted
                    ? ('Includes extra time for upcoming ' . ($holidayWindow['adjustment_reason'] ?: 'holiday non-operational dates'))
                    : null;

                $eta = $isHolidayAdjusted
                    ? 'Delivered by: ' . $holidayWindow['formatted_range']
                    : ($method->starts_after_artwork_approval
                        ? 'After artwork approval: ' . $totalMin . '–' . $totalMax . ' business days'
                        : 'Estimated delivery: ' . $totalMin . '–' . $totalMax . ' business days');

                return [
                    'id' => $method->id,
                    'code' => (string) $method->code,
                    'title' => (string) $method->name,
                    'description' => (string) ($method->description ?? ''),
                    'eta' => $eta,
                    'holiday_adjusted' => $isHolidayAdjusted,
                    'holiday_notice' => $holidayNotice,
                    'holiday_delivery_range' => $holidayWindow['formatted_range'],
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
                        'holiday_adjusted' => $isHolidayAdjusted,
                        'formatted_range' => $holidayWindow['formatted_range'],
                        'product_lines' => $productionWindow['lines'] ?? [],
                    ],
                ];
            })
            ->values()
            ->all();
    }


    private function methodBaseCharge(ShippingMethod $method, int $quantity, int $lineCount): float
    {
        $application = method_exists($method, 'chargeApplication') ? $method->chargeApplication() : (string) ($method->charge_application ?? '');
        $amount = method_exists($method, 'effectiveChargeAmount') ? $method->effectiveChargeAmount() : (float) ($method->charge_amount ?? 0);

        if ($application !== '') {
            return round(match ($application) {
                'included' => 0.00,
                'per_item' => $amount * max(0, $quantity),
                'per_product' => $amount * max(1, $lineCount),
                default => $amount,
            }, 2);
        }

        return round((float) $method->base_price + ((float) $method->per_item_price * max(0, $quantity - 1)), 2);
    }

    /**
     * Checkout consumes the cart's already-repriced, once-per-order shipping
     * allocation. Do not fetch products or multiply the table fee again here.
     */
    public function productShippingTotal(array $cart): array
    {
        $lines = [];

        foreach ((array) ($cart['items'] ?? []) as $item) {
            $snapshot = Arr::get($item, 'customization.fulfillment.shipping');
            if (! is_array($snapshot)) {
                continue;
            }

            $amount = round(max(0, (float) ($item['product_shipping_total'] ?? 0)), 2);
            $lines[] = [
                'item_key' => (string) ($item['key'] ?? ''),
                'product' => Arr::get($item, 'product.short_title', Arr::get($item, 'product.title', 'Product')),
                'method' => (string) ($snapshot['label'] ?? 'Selected shipping'),
                'code' => (string) ($snapshot['code'] ?? ''),
                'charge_type' => (string) ($snapshot['charge_type'] ?? 'configured_product'),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'amount' => $amount,
                'display_amount' => '$'.number_format($amount, 2),
                'price_status' => (string) ($snapshot['price_status'] ?? 'priced'),
                'minimum_days' => max(0, (int) ($snapshot['minimum_days'] ?? 0)),
                'maximum_days' => max(0, (int) ($snapshot['maximum_days'] ?? $snapshot['minimum_days'] ?? 0)),
            ];
        }

        return [
            'total' => round(max(0, (float) ($cart['product_shipping_total'] ?? 0)), 2),
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
            $snapshot = Arr::get($item, 'customization.fulfillment.production');

            if (is_array($snapshot)) {
                $min = max(0, (int) ($snapshot['minimum_days'] ?? 0));
                $max = max($min, (int) ($snapshot['maximum_days'] ?? $min));
                $minimumDays = max($minimumDays, $min);
                $maximumDays = max($maximumDays, $max);
                $lines[] = [
                    'item_key' => (string) ($item['key'] ?? ''),
                    'product' => Arr::get($item, 'product.short_title', Arr::get($item, 'product.title', 'Product')),
                    'production_option' => (string) ($snapshot['label'] ?? 'Standard production'),
                    'code' => (string) ($snapshot['code'] ?? $speedCode),
                    'minimum_days' => $min,
                    'maximum_days' => $max,
                    'amount' => round(max(0, (float) ($snapshot['amount'] ?? 0)), 2),
                    'display_amount' => (string) ($snapshot['display_amount'] ?? '$'.number_format(max(0, (float) ($snapshot['amount'] ?? 0)), 2)),
                ];

                continue;
            }

            if ($slug === '') {
                continue;
            }

            $product = Product::query()->where('slug', $slug)->with('productionSpeeds.productionMethod')->first();
            $speed = $product?->productionSpeeds?->where('is_active', true)->firstWhere('code', $speedCode)
                ?? $product?->productionSpeeds?->where('is_active', true)->sortBy('sort_order')->first();

            $method = $speed?->relationLoaded('productionMethod') ? $speed?->productionMethod : null;
            // Production timing belongs to the product quantity rule, not Master Data.
            $min = max(0, (int) ($speed?->minimum_days ?? 0));
            $max = max($min, (int) ($speed?->maximum_days ?? $min));
            $amount = round(max(0, (float) ($speed?->price_adjustment ?? 0)) * max(1, (int) ($item['quantity'] ?? 1)), 2);

            $minimumDays = max($minimumDays, $min);
            $maximumDays = max($maximumDays, $max);

            if ($product instanceof Product && $speed !== null) {
                $lines[] = [
                    'item_key' => (string) ($item['key'] ?? ''),
                    'product' => Arr::get($item, 'product.short_title', $product->name),
                    'production_option' => ($method?->name ?: $speed?->name) ?: 'Standard production',
                    'code' => (string) ($speed?->code ?? $speedCode),
                    'minimum_days' => $min,
                    'maximum_days' => $max,
                    'amount' => $amount,
                    'display_amount' => $amount > 0 ? '$'.number_format($amount, 2) : 'Included',
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
                'charge_amount' => 12.00,
                'charge_application' => 'per_order',
                'base_price' => 12.00,
                'per_item_price' => 2.25,
                'free_shipping_minimum' => null,
                'minimum_days' => 5,
                'maximum_days' => 7,
                'starts_after_artwork_approval' => true,
                'is_default' => true,
                'is_active' => true,
            ]),
        ];
    }
}
