<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OrderExperienceService
{
    private const CHECKOUT_SESSION_KEY = 'nextplay_checkout';
    private const TRACKED_ORDER_KEY = 'nextplay_tracked_order';

    public function pageData(?array $order = null, bool $allowDemo = true): array
    {
        $resolved = $order;

        if (! is_array($resolved) && $allowDemo) {
            $resolved = $this->currentOrder() ?? $this->demoOrder();
        }

        $normalized = is_array($resolved) ? $this->normalizeOrder($resolved) : null;

        return [
            'order' => $normalized,
            'timeline' => $normalized ? $this->timeline($normalized) : [],
            'trackingTimeline' => $normalized ? $this->trackingTimeline($normalized) : [],
            'supportTips' => $this->supportTips(),
            'orderSummary' => $normalized ? $this->summary($normalized) : [
                'order_number' => null,
                'items' => [],
                'totals' => [],
            ],
            'seo' => [
                'title' => 'Order Updates | NextPlay Sportswear',
                'description' => 'Secure order status, payment, tracking, invoice, and confirmation pages for NextPlay Sportswear customers.',
                'robots' => 'noindex, nofollow',
            ],
        ];
    }

    public function currentOrder(): ?array
    {
        $state = session(self::CHECKOUT_SESSION_KEY, []);
        $order = $state['placed_order'] ?? null;

        return is_array($order) ? $order : null;
    }

    /**
     * Reload the verified order from the database on every request so tracking
     * always reflects the latest admin, payment, fulfillment, and shipment state.
     */
    public function trackedOrder(): ?array
    {
        $state = session(self::TRACKED_ORDER_KEY);

        if (! is_array($state)) {
            return null;
        }

        $query = $this->trackingQuery();

        if (! empty($state['order_id'])) {
            $order = $query->whereKey((int) $state['order_id'])->first();
        } elseif (filled($state['order_number'] ?? null) && filled($state['customer_email'] ?? null)) {
            // Backward compatibility for sessions created by the previous
            // snapshot-based tracker. Re-verify the email before upgrading it.
            $order = $query
                ->where('order_number', Str::upper(trim((string) $state['order_number'])))
                ->whereRaw('LOWER(customer_email) = ?', [Str::lower(trim((string) $state['customer_email']))])
                ->first();
        } else {
            $order = null;
        }

        if (! $order instanceof Order) {
            session()->forget(self::TRACKED_ORDER_KEY);

            return null;
        }

        session()->put(self::TRACKED_ORDER_KEY, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $this->orderSnapshot($order);
    }

    public function lookupForTracking(array $payload): ?array
    {
        $identifier = trim((string) ($payload['order_number'] ?? ''));
        $orderNumber = Str::upper(ltrim($identifier, "# \t\n\r\0\x0B"));
        $email = Str::lower(trim((string) ($payload['email'] ?? '')));

        if ($identifier === '' || $email === '') {
            return null;
        }

        $order = $this->trackingQuery()
            ->where('order_number', $orderNumber)
            ->whereRaw('LOWER(customer_email) = ?', [$email])
            ->first();

        // Also accept an actual shipment tracking number in the same field.
        // This keeps the prototype unchanged while making "tracking ID + email"
        // work after a shipment has been created.
        if (! $order instanceof Order) {
            $trackingNumber = Str::lower(preg_replace('/\s+/', '', $identifier) ?? $identifier);
            $order = $this->trackingQuery()
                ->whereRaw('LOWER(customer_email) = ?', [$email])
                ->whereHas('shipments', function ($query) use ($trackingNumber): void {
                    $query->whereRaw("REPLACE(LOWER(tracking_number), ' ', '') = ?", [$trackingNumber]);
                })
                ->first();
        }

        if (! $order instanceof Order) {
            session()->forget(self::TRACKED_ORDER_KEY);

            return null;
        }

        session()->put(self::TRACKED_ORDER_KEY, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $this->orderSnapshot($order);
    }

    public function orderForNumber(?string $orderNumber = null, bool $allowDemo = true): ?array
    {
        $current = $this->currentOrder();

        if ($orderNumber !== null) {
            $requested = Str::upper(trim($orderNumber));

            foreach ([$this->trackedOrder(), $current] as $order) {
                if (! is_array($order)) {
                    continue;
                }

                if (hash_equals(Str::upper((string) ($order['order_number'] ?? '')), $requested)) {
                    return $this->normalizeOrder($order);
                }
            }
        }

        if ($orderNumber === null && is_array($current)) {
            return $this->normalizeOrder($current);
        }

        if ($allowDemo) {
            return $this->demoOrder();
        }

        return null;
    }

    public function demoOrder(): array
    {
        return $this->normalizeOrder([
            'order_number' => 'NP-DEMO-10482',
            'status' => 'design_review',
            'payment_status' => 'verified',
            'customer_email' => 'customer@example.com',
            'customer_name' => 'NextPlay Customer',
            'placed_at' => now()->subHours(3)->toIso8601String(),
            'is_demo' => true,
            'items' => [
                [
                    'product' => [
                        'title' => 'Custom Football Jersey',
                        'image' => asset('storage/storefront/home/football.webp'),
                        'alt' => 'Custom football jersey',
                    ],
                    'quantity' => 2,
                    'unit_price' => 39.00,
                    'line_total' => 78.00,
                    'customization' => [
                        'design_option' => 'Default Team Style',
                        'size_summary' => 'Men L x2',
                        'notes' => 'Name: Miller, Number: 24, Navy / Red colorway',
                    ],
                ],
                [
                    'product' => [
                        'title' => 'Custom Team Hoodie',
                        'image' => asset('storage/storefront/home/hoodies.webp'),
                        'alt' => 'Custom team hoodie',
                    ],
                    'quantity' => 1,
                    'unit_price' => 45.00,
                    'line_total' => 45.00,
                    'customization' => [
                        'design_option' => 'Logo Front Print',
                        'size_summary' => 'Adult M x1',
                        'notes' => 'Navy hoodie with front chest team logo',
                    ],
                ],
            ],
            'totals' => [
                'subtotal' => 123.00,
                'customization_total' => 0.00,
                'discount' => 9.00,
                'shipping' => 12.00,
                'tax' => 0.00,
                'total' => 126.00,
                'quantity' => 3,
            ],
            'information' => [
                'email' => 'customer@example.com',
                'phone' => '+1 000 000 0000',
                'first_name' => 'NextPlay',
                'last_name' => 'Customer',
                'order_type' => 'Team order',
                'proof_preference' => 'Yes, send proof before production',
                'order_note' => 'Please confirm artwork before production.',
            ],
            'shipping_address' => [
                'label' => 'River Valley Baseball Club',
                'address' => [
                    'first_name' => 'NextPlay',
                    'last_name' => 'Customer',
                    'company_name' => 'River Valley Baseball Club',
                    'address_line_1' => '421 West Field Road',
                    'address_line_2' => null,
                    'city' => 'Columbus',
                    'state' => 'OH',
                    'postal_code' => '43004',
                    'country' => 'United States',
                    'phone' => '+1 000 000 0000',
                    'email' => 'customer@example.com',
                ],
            ],
            'billing_address' => [
                'same_as_shipping' => true,
                'label' => 'Same as shipping address',
            ],
            'shipping_method' => [
                'title' => 'Standard Shipping',
                'eta' => 'Estimated after production: 5–7 business days',
                'display_price' => '$12.00',
            ],
            'payment_method' => [
                'method' => 'card',
                'label' => 'Credit / Debit Card',
                'display' => [
                    'brand' => 'Visa',
                    'last_four' => '4242',
                ],
            ],
        ]);
    }

    public function normalizeOrder(array $order): array
    {
        $totals = (array) ($order['totals'] ?? []);
        $totalsSeparated = (bool) data_get($order, 'shipping_method.totals_separated', false);
        $rawCustomizationTotal = round((float) ($totals['customization_total'] ?? 0), 2);
        $productShippingTotal = round((float) ($totals['product_shipping_total'] ?? 0), 2);
        $rawShippingTotal = round((float) ($totals['shipping'] ?? 0), 2);
        $displayCustomizationTotal = $totalsSeparated
            ? $rawCustomizationTotal
            : round(max(0, $rawCustomizationTotal - $productShippingTotal), 2);
        $displayShippingTotal = $totalsSeparated
            ? $rawShippingTotal
            : round(max(0, $rawShippingTotal + $productShippingTotal), 2);

        $items = collect((array) ($order['items'] ?? []))->map(function (array $item): array {
            $product = (array) ($item['product'] ?? []);
            $customization = (array) ($item['customization'] ?? []);
            $quantity = (int) ($item['quantity'] ?? 1);
            $lineTotal = (float) ($item['line_total'] ?? (($item['unit_price'] ?? 0) * $quantity));

            return [
                'title' => (string) ($product['title'] ?? $product['short_title'] ?? 'Custom Product'),
                'image' => (string) ($product['image'] ?? asset('storage/storefront/home/football.webp')),
                'alt' => (string) ($product['alt'] ?? $product['title'] ?? 'Custom sportswear product'),
                'quantity' => $quantity,
                'unit_price' => round((float) ($item['unit_price'] ?? 0), 2),
                'line_total' => round($lineTotal, 2),
                'customization' => [
                    'design_option' => (string) ($customization['design_option'] ?? 'Default Team Style'),
                    'size_summary' => (string) ($customization['size_summary'] ?? 'Sizes confirmed during proof review'),
                    'artwork_status' => (string) ($customization['artwork_status'] ?? 'Artwork/logo can be sent now or later'),
                    'artwork_files' => collect((array) ($customization['artwork_files'] ?? []))
                        ->filter(fn ($file) => is_array($file) && filled($file['original_name'] ?? null))
                        ->map(fn ($file) => [
                            'original_name' => (string) $file['original_name'],
                            'size' => max(0, (int) ($file['size'] ?? 0)),
                            'mime_type' => (string) ($file['mime_type'] ?? 'application/octet-stream'),
                        ])->values()->all(),
                    'fulfillment' => (array) ($customization['fulfillment'] ?? []),
                    'notes' => (string) ($customization['notes'] ?? ''),
                ],
            ];
        })->values()->all();

        $placedAt = isset($order['placed_at']) ? Carbon::parse($order['placed_at']) : now();
        $estimatedMinimumDays = (int) collect($items)->max(fn (array $item): int => max(0, (int) data_get($item, 'customization.fulfillment.estimated_minimum_days', 0)));
        $estimatedMaximumDays = (int) collect($items)->max(fn (array $item): int => max(0, (int) data_get($item, 'customization.fulfillment.estimated_maximum_days', 0)));

        if ($estimatedMaximumDays <= 0) {
            $estimatedMinimumDays = 8;
            $estimatedMaximumDays = 12;
        }

        $estimatedMaximumDays = max($estimatedMinimumDays, $estimatedMaximumDays);
        $estimatedStart = $placedAt->copy()->addWeekdays($estimatedMinimumDays)->format('M d');
        $estimatedEnd = $placedAt->copy()->addWeekdays($estimatedMaximumDays)->format('M d');

        return array_merge($order, [
            'order_number' => (string) ($order['order_number'] ?? 'NP-' . now()->format('ymd') . '-PENDING'),
            'status' => (string) ($order['status'] ?? 'design_review'),
            'payment_status' => (string) ($order['payment_status'] ?? 'pending'),
            'customer_email' => (string) ($order['customer_email'] ?? Arr::get($order, 'information.email', 'customer@example.com')),
            'customer_name' => trim((string) ($order['customer_name'] ?? '')) ?: trim((Arr::get($order, 'information.first_name', 'NextPlay') . ' ' . Arr::get($order, 'information.last_name', 'Customer'))),
            'placed_display' => $placedAt->format('M d, Y · g:i A'),
            'estimated_delivery' => $estimatedStart . '–' . $estimatedEnd,
            'items' => $items,
            'totals' => [
                'subtotal' => round((float) ($totals['subtotal'] ?? collect($items)->sum('line_total')), 2),
                'customization_total' => $displayCustomizationTotal,
                'discount' => round((float) ($totals['discount'] ?? 0), 2),
                'referral_discount' => round((float) ($totals['referral_discount'] ?? data_get($order, 'information.referral_offer.amount', 0)), 2),
                'coupon_code' => (string) ($totals['coupon_code'] ?? ($order['coupon_code'] ?? '')),
                'shipping' => $displayShippingTotal,
                'rural_surcharge' => round((float) ($totals['rural_surcharge'] ?? 0), 2),
                'product_shipping_total' => $productShippingTotal,
                'tax' => round((float) ($totals['tax'] ?? 0), 2),
                'total' => round((float) ($totals['total'] ?? collect($items)->sum('line_total')), 2),
                'quantity' => (int) ($totals['quantity'] ?? collect($items)->sum('quantity')),
            ],
            'is_demo' => (bool) ($order['is_demo'] ?? false),
            'histories' => array_values((array) ($order['histories'] ?? [])),
            'shipments' => array_values((array) ($order['shipments'] ?? [])),
        ]);
    }

    /**
     * Build the public progress line from the actual order-status vocabulary used
     * by this project. Exceptional states are represented explicitly instead of
     * being forced into a fake linear prototype stage.
     */
    public function trackingTimeline(array $order): array
    {
        $status = (string) ($order['status'] ?? 'pending_payment');
        $paymentStatus = (string) ($order['payment_status'] ?? 'pending');

        $steps = [
            [
                'key' => 'placed',
                'title' => 'Order Placed',
                'description' => 'Your NextPlay order was received successfully.',
            ],
            [
                'key' => 'payment',
                'title' => match ($paymentStatus) {
                    'paid' => 'Payment Confirmed',
                    'processing' => 'Payment Processing',
                    'failed' => 'Payment Failed',
                    'partially_refunded' => 'Payment Partially Refunded',
                    'refunded' => 'Payment Refunded',
                    default => $status === 'quote_invoice_requested' ? 'Invoice Requested' : 'Payment Review',
                },
                'description' => match ($paymentStatus) {
                    'paid' => 'Your payment has been confirmed.',
                    'processing' => 'Your payment is being verified.',
                    'failed' => 'The payment attempt was not completed successfully.',
                    'partially_refunded' => 'Part of the confirmed payment has been refunded.',
                    'refunded' => 'The confirmed payment has been refunded.',
                    default => $status === 'quote_invoice_requested'
                        ? 'Your order is waiting for invoice or manual payment processing.'
                        : 'Your payment is waiting for confirmation.',
                },
            ],
            [
                'key' => 'design_review',
                'title' => 'Design Review',
                'description' => 'Artwork, logo placement, names, numbers, sizes, and order details are being reviewed.',
            ],
            [
                'key' => 'proof_approval',
                'title' => 'Proof Approval',
                'description' => 'The final design proof is reviewed and approved before production.',
            ],
            [
                'key' => 'in_production',
                'title' => 'In Production',
                'description' => 'Your approved items are being produced and customised.',
            ],
            [
                'key' => 'shipped',
                'title' => $status === 'partially_shipped' ? 'Partially Shipped' : 'Shipped',
                'description' => $status === 'partially_shipped'
                    ? 'Part of your order has shipped. Remaining items are still being fulfilled.'
                    : 'Your order has left production and is with the carrier.',
            ],
            [
                'key' => 'delivered',
                'title' => 'Delivered',
                'description' => 'Your order has been delivered.',
            ],
            [
                'key' => 'completed',
                'title' => 'Completed',
                'description' => 'Your order workflow is complete.',
            ],
        ];

        $currentIndex = $this->progressIndexForStatus($status);

        if (in_array($status, ['cancelled', 'on_hold'], true)) {
            $furthestIndex = $this->furthestProgressIndex($order);
            $completedThrough = max(0, $furthestIndex - 1);
            $visible = [];

            foreach ($steps as $index => $step) {
                if ($index > $completedThrough) {
                    break;
                }

                $visible[] = array_merge($step, ['state' => 'done']);
            }

            $visible[] = [
                'key' => $status,
                'title' => (string) config('commerce.order_statuses.'.$status, Str::headline($status)->toString()),
                'description' => $status === 'cancelled'
                    ? 'This order has been cancelled and will not continue through fulfillment.'
                    : 'This order is currently on hold. Contact support if you need more information.',
                'state' => 'current',
            ];

            if ($status === 'on_hold') {
                foreach ($steps as $index => $step) {
                    if ($index >= max(1, $furthestIndex)) {
                        $visible[] = array_merge($step, ['state' => 'pending']);
                    }
                }
            }

            return $visible;
        }

        return collect($steps)->map(function (array $step, int $index) use ($currentIndex, $status, $paymentStatus): array {
            $state = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'pending');

            if ($index === 1 && $paymentStatus === 'failed') {
                $state = 'current';
            }

            if ($index === 1 && in_array($paymentStatus, ['partially_refunded', 'refunded'], true) && $currentIndex > 1) {
                $state = 'done';
            }

            if ($status === 'payment_failed' && $index > 1) {
                $state = 'pending';
            }

            return array_merge($step, ['state' => $state]);
        })->all();
    }

    public function timeline(array $order): array
    {
        $status = (string) ($order['status'] ?? 'design_review');
        $paymentStatus = (string) ($order['payment_status'] ?? 'pending');
        $placed = (string) ($order['placed_display'] ?? now()->format('M d, Y · g:i A'));

        return [
            [
                'title' => 'Order Placed',
                'description' => 'Your custom sportswear order was received securely.',
                'time' => $placed,
                'state' => 'done',
            ],
            [
                'title' => $paymentStatus === 'verified' || $paymentStatus === 'paid' ? 'Payment Verified' : 'Payment Review',
                'description' => $paymentStatus === 'verified' || $paymentStatus === 'paid'
                    ? 'Payment was verified securely by the payment provider.'
                    : 'Payment will be confirmed by secure provider webhook or admin invoice review.',
                'time' => $paymentStatus === 'verified' || $paymentStatus === 'paid' ? $placed : 'Pending verification',
                'state' => $paymentStatus === 'verified' || $paymentStatus === 'paid' ? 'done' : 'current',
            ],
            [
                'title' => 'Design Review',
                'description' => 'Artwork, logo placement, names, numbers, sizes, and notes are checked before production.',
                'time' => in_array($status, ['design_review', 'pending_payment', 'quote_invoice_requested'], true) ? 'In progress' : 'Upcoming',
                'state' => in_array($status, ['design_review', 'pending_payment', 'quote_invoice_requested'], true) ? 'current' : 'pending',
            ],
            [
                'title' => 'Production',
                'description' => 'Production begins after artwork and customization details are confirmed.',
                'time' => 'Upcoming',
                'state' => 'pending',
            ],
            [
                'title' => 'Shipped',
                'description' => 'Tracking number will appear after the package leaves production.',
                'time' => 'Upcoming',
                'state' => 'pending',
            ],
        ];
    }

    public function summary(array $order): array
    {
        return [
            'order_number' => $order['order_number'],
            'items' => $order['items'],
            'totals' => $order['totals'],
        ];
    }

    public function addressLines(array $addressWrapper): array
    {
        $address = (array) ($addressWrapper['address'] ?? $addressWrapper);

        return array_filter([
            trim(((string) ($address['first_name'] ?? '')) . ' ' . ((string) ($address['last_name'] ?? ''))),
            $address['company_name'] ?? null,
            $address['address_line_1'] ?? null,
            $address['address_line_2'] ?? null,
            trim(((string) ($address['city'] ?? '')) . ', ' . ((string) ($address['state'] ?? '')) . ' ' . ((string) ($address['postal_code'] ?? ''))),
            $address['country'] ?? null,
            $address['phone'] ?? null,
        ]);
    }

    private function trackingQuery()
    {
        return Order::query()->with([
            'items',
            'histories',
            'shipments',
        ]);
    }

    private function orderSnapshot(Order $order): array
    {
        $order->loadMissing(['items', 'histories', 'shipments']);

        return $this->normalizeOrder([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'customer_email' => $order->customer_email,
            'customer_name' => $order->customer_name,
            'items' => $order->items->map(fn ($item): array => [
                'product' => [
                    'title' => $item->product_name,
                    'image' => $item->image_url ?: asset('images/product-placeholder.svg'),
                    'alt' => $item->product_name,
                    'slug' => $item->product_slug,
                    'sku' => $item->sku,
                ],
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'customization_unit_price' => (float) $item->customization_unit_price,
                'line_total' => (float) $item->line_total,
                'customization' => (array) ($item->customization ?? []),
            ])->all(),
            'totals' => [
                'subtotal' => (float) $order->subtotal,
                'customization_total' => (float) $order->customization_total,
                'discount' => (float) $order->discount_total,
                'referral_discount' => (float) data_get($order->information, 'referral_offer.amount', 0),
                'coupon_code' => $order->coupon_code,
                'shipping' => (float) $order->shipping_total,
                'rural_surcharge' => (float) ($order->rural_surcharge_total ?? 0),
                'product_shipping_total' => (float) ($order->product_shipping_total ?? 0),
                'tax' => (float) $order->tax_total,
                'total' => (float) $order->grand_total,
                'quantity' => (int) $order->total_quantity,
            ],
            'information' => (array) ($order->information ?? []),
            'shipping_address' => (array) ($order->shipping_address ?? []),
            'billing_address' => (array) ($order->billing_address ?? []),
            'shipping_method' => (array) ($order->shipping_method ?? []),
            'payment_method' => (array) ($order->payment_method ?? []),
            'placed_at' => $order->placed_at?->toIso8601String(),
            'histories' => $order->histories
                ->sortBy('occurred_at')
                ->map(fn ($history): array => [
                    'status' => (string) $history->status,
                    'title' => (string) $history->title,
                    'description' => (string) ($history->description ?? ''),
                    'occurred_at' => $history->occurred_at?->toIso8601String(),
                ])->values()->all(),
            'shipments' => $order->shipments->map(fn ($shipment): array => [
                'shipment_number' => (string) $shipment->shipment_number,
                'status' => (string) $shipment->status,
                'status_label' => $shipment->statusLabel(),
                'carrier' => (string) ($shipment->carrier ?? ''),
                'service' => (string) ($shipment->service ?? ''),
                'tracking_number' => (string) ($shipment->tracking_number ?? ''),
                'tracking_url' => (string) ($shipment->tracking_url ?? ''),
                'shipped_at' => $shipment->shipped_at?->toIso8601String(),
                'estimated_delivery_at' => $shipment->estimated_delivery_at?->toIso8601String(),
                'delivered_at' => $shipment->delivered_at?->toIso8601String(),
            ])->values()->all(),
            'is_demo' => false,
        ]);
    }

    private function progressIndexForStatus(string $status): int
    {
        return match ($status) {
            'pending_payment', 'payment_review', 'payment_failed', 'quote_invoice_requested' => 1,
            'design_review' => 2,
            'proof_approval' => 3,
            'in_production' => 4,
            'partially_shipped', 'shipped' => 5,
            'delivered' => 6,
            'completed' => 7,
            default => 0,
        };
    }

    private function furthestProgressIndex(array $order): int
    {
        $indices = collect((array) ($order['histories'] ?? []))
            ->map(fn ($history): int => $this->progressIndexForStatus((string) data_get($history, 'status', '')))
            ->push($this->progressIndexForStatus((string) ($order['status'] ?? '')))
            ->filter(fn (int $index): bool => $index >= 0);

        return max(0, (int) $indices->max());
    }

    private function supportTips(): array
    {
        return [
            ['title' => 'Need changes?', 'body' => 'Contact support before artwork approval or production release.'],
            ['title' => 'Bulk order?', 'body' => 'Keep team list, logo files, and deadline details ready.'],
            ['title' => 'Design proof', 'body' => 'Production starts after final design details are approved.'],
        ];
    }
}
