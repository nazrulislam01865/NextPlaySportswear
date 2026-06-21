<?php

namespace App\Services\Order;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OrderExperienceService
{
    private const CHECKOUT_SESSION_KEY = 'nextplay_checkout';
    private const TRACKED_ORDER_KEY = 'nextplay_tracked_order';

    public function pageData(?array $order = null): array
    {
        $order = $this->normalizeOrder($order ?? $this->currentOrder() ?? $this->demoOrder());

        return [
            'order' => $order,
            'timeline' => $this->timeline($order),
            'supportTips' => $this->supportTips(),
            'orderSummary' => $this->summary($order),
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

    public function trackedOrder(): ?array
    {
        $order = session(self::TRACKED_ORDER_KEY);

        return is_array($order) ? $order : null;
    }

    public function lookupForTracking(array $payload): ?array
    {
        $orderNumber = Str::upper(trim((string) ($payload['order_number'] ?? '')));
        $email = Str::lower(trim((string) ($payload['email'] ?? '')));
        $order = $this->currentOrder();

        if (! is_array($order)) {
            return null;
        }

        $matchesOrder = hash_equals(Str::upper((string) ($order['order_number'] ?? '')), $orderNumber);
        $matchesEmail = hash_equals(Str::lower((string) ($order['customer_email'] ?? '')), $email);

        if (! $matchesOrder || ! $matchesEmail) {
            return null;
        }

        $normalized = $this->normalizeOrder($order);
        session()->put(self::TRACKED_ORDER_KEY, $normalized);

        return $normalized;
    }

    public function orderForNumber(?string $orderNumber = null, bool $allowDemo = true): ?array
    {
        $order = $this->currentOrder();

        if ($orderNumber !== null && is_array($order)) {
            $matches = hash_equals(
                Str::upper((string) ($order['order_number'] ?? '')),
                Str::upper(trim($orderNumber))
            );

            if ($matches) {
                return $this->normalizeOrder($order);
            }
        }

        if ($orderNumber === null && is_array($order)) {
            return $this->normalizeOrder($order);
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
                        'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=300&q=80',
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
                        'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=300&q=80',
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
        $items = collect((array) ($order['items'] ?? []))->map(function (array $item): array {
            $product = (array) ($item['product'] ?? []);
            $customization = (array) ($item['customization'] ?? []);
            $quantity = (int) ($item['quantity'] ?? 1);
            $lineTotal = (float) ($item['line_total'] ?? (($item['unit_price'] ?? 0) * $quantity));

            return [
                'title' => (string) ($product['title'] ?? $product['short_title'] ?? 'Custom Product'),
                'image' => (string) ($product['image'] ?? 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=300&q=80'),
                'alt' => (string) ($product['alt'] ?? $product['title'] ?? 'Custom sportswear product'),
                'quantity' => $quantity,
                'unit_price' => round((float) ($item['unit_price'] ?? 0), 2),
                'line_total' => round($lineTotal, 2),
                'customization' => [
                    'design_option' => (string) ($customization['design_option'] ?? 'Default Team Style'),
                    'size_summary' => (string) ($customization['size_summary'] ?? 'Sizes confirmed during proof review'),
                    'artwork_status' => (string) ($customization['artwork_status'] ?? 'Artwork/logo can be sent now or later'),
                    'notes' => (string) ($customization['notes'] ?? ''),
                ],
            ];
        })->values()->all();

        $placedAt = isset($order['placed_at']) ? Carbon::parse($order['placed_at']) : now();
        $estimatedStart = $placedAt->copy()->addWeekdays(8)->format('M d');
        $estimatedEnd = $placedAt->copy()->addWeekdays(12)->format('M d');

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
                'customization_total' => round((float) ($totals['customization_total'] ?? 0), 2),
                'discount' => round((float) ($totals['discount'] ?? 0), 2),
                'shipping' => round((float) ($totals['shipping'] ?? 0), 2),
                'tax' => round((float) ($totals['tax'] ?? 0), 2),
                'total' => round((float) ($totals['total'] ?? collect($items)->sum('line_total')), 2),
                'quantity' => (int) ($totals['quantity'] ?? collect($items)->sum('quantity')),
            ],
            'is_demo' => (bool) ($order['is_demo'] ?? false),
        ]);
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

    private function supportTips(): array
    {
        return [
            ['title' => 'Need changes?', 'body' => 'Contact support before artwork approval or production release.'],
            ['title' => 'Bulk order?', 'body' => 'Keep team list, logo files, and deadline details ready.'],
            ['title' => 'Design proof', 'body' => 'Production starts after final design details are approved.'],
        ];
    }
}
