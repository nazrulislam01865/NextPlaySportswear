<?php

namespace App\Services\Checkout;

use App\Models\CustomerAddress;
use App\Models\CustomerPaymentMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutService
{
    private const SESSION_KEY = 'nextplay_checkout';

    public function __construct(private readonly CartService $cart)
    {
    }

    public function pageData(?User $user = null): array
    {
        $cart = $this->cart->summary();
        $state = $this->state();

        return [
            'cart' => $cart,
            'state' => $state,
            'steps' => $this->steps(),
            'savedAddresses' => $this->savedAddresses($user),
            'savedPaymentMethods' => $this->savedPaymentMethods($user),
            'shippingMethods' => $this->shippingMethods($cart),
            'paymentOptions' => $this->paymentOptions(),
            'summary' => $this->checkoutSummary($cart, $state),
        ];
    }

    public function hasCheckoutItems(): bool
    {
        return ! $this->cart->summary()['is_empty'];
    }

    public function storeInformation(array $payload): void
    {
        $this->mergeState('information', [
            'email' => Str::lower(trim((string) $payload['email'])),
            'phone' => trim((string) $payload['phone']),
            'first_name' => trim((string) $payload['first_name']),
            'last_name' => trim((string) $payload['last_name']),
            'order_type' => (string) $payload['order_type'],
            'delivery_deadline' => $payload['delivery_deadline'] ?? null,
            'proof_preference' => (string) $payload['proof_preference'],
            'order_note' => Str::limit(trim((string) ($payload['order_note'] ?? '')), 1200, ''),
        ]);
    }

    public function storeShippingAddress(array $payload, ?User $user = null): void
    {
        $this->mergeState('shipping_address', $this->resolveAddressPayload($payload, $user, 'shipping'));
    }

    public function storeBillingAddress(array $payload, ?User $user = null): void
    {
        if ((bool) ($payload['same_as_shipping'] ?? false)) {
            $this->mergeState('billing_address', [
                'same_as_shipping' => true,
                'label' => 'Same as shipping address',
                'address' => $this->state()['shipping_address']['address'] ?? [],
            ]);

            return;
        }

        $billing = $this->resolveAddressPayload($payload, $user, 'billing');
        $billing['same_as_shipping'] = false;

        $this->mergeState('billing_address', $billing);
    }

    public function storeShippingMethod(array $payload): void
    {
        $methodCode = (string) $payload['shipping_method'];
        $methods = collect($this->shippingMethods($this->cart->summary()))->keyBy('code');
        $method = $methods->get($methodCode) ?? $methods->first();

        $this->mergeState('shipping_method', $method);
    }

    public function storePaymentMethod(array $payload, ?User $user = null): void
    {
        $method = (string) $payload['payment_method'];
        $payment = [
            'method' => $method,
            'label' => $this->paymentOptions()[$method]['title'] ?? 'Payment method',
            'requires_provider_redirect' => in_array($method, ['card', 'paypal'], true),
            'saved_payment_method_id' => null,
            'display' => null,
        ];

        if ($method === 'saved_card' && $user instanceof User) {
            $saved = $user->customerPaymentMethods()->whereKey((int) ($payload['saved_payment_method_id'] ?? 0))->first();

            if ($saved instanceof CustomerPaymentMethod) {
                $payment['saved_payment_method_id'] = $saved->id;
                $payment['label'] = $saved->maskedLabel();
                $payment['display'] = [
                    'brand' => $saved->brand,
                    'last_four' => $saved->last_four,
                    'expiry' => $saved->expiryLabel(),
                ];
            }
        }

        if ($method === 'card') {
            $payment['label'] = 'Credit / Debit Card';
            $payment['display'] = [
                'note' => 'Card details will be collected by the secure payment provider during payment.',
            ];
        }

        if ($method === 'invoice') {
            $payment['requires_provider_redirect'] = false;
            $payment['label'] = 'Request invoice for bulk/custom order';
        }

        $this->mergeState('payment_method', $payment);
    }

    public function confirmReview(array $payload): void
    {
        $this->mergeState('review', [
            'confirmed' => (bool) ($payload['confirm_details'] ?? false),
            'confirmed_at' => now()->toIso8601String(),
        ]);
    }

    public function placeOrder(array $payload, ?User $user = null): array
    {
        $state = $this->state();
        $cart = $this->cart->summary();
        $summary = $this->checkoutSummary($cart, $state);

        $idempotencyKey = (string) ($payload['idempotency_key'] ?? '');
        $existing = $state['placed_order'] ?? null;

        if (is_array($existing) && hash_equals((string) ($existing['idempotency_key'] ?? ''), $idempotencyKey)) {
            return $existing;
        }

        $order = [
            'order_number' => 'NP-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
            'status' => $summary['payment_method']['method'] === 'invoice' ? 'quote_invoice_requested' : 'pending_payment',
            'payment_status' => 'pending',
            'idempotency_key' => $idempotencyKey,
            'user_id' => $user?->id,
            'customer_email' => $state['information']['email'] ?? $user?->email,
            'customer_name' => trim(($state['information']['first_name'] ?? '') . ' ' . ($state['information']['last_name'] ?? '')),
            'items' => $cart['items'],
            'totals' => Arr::only($summary, ['subtotal', 'customization_total', 'discount', 'shipping', 'tax', 'total', 'quantity']),
            'information' => $state['information'] ?? [],
            'shipping_address' => $state['shipping_address'] ?? [],
            'billing_address' => $state['billing_address'] ?? [],
            'shipping_method' => $state['shipping_method'] ?? [],
            'payment_method' => $summary['payment_method'],
            'placed_at' => now()->toIso8601String(),
            'next_step' => $summary['payment_method']['method'] === 'invoice' ? 'admin_invoice_review' : 'payment_provider_redirect',
        ];

        $this->mergeState('placed_order', $order);

        Log::info('Checkout order snapshot created', [
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'user_id' => $order['user_id'],
        ]);

        return $order;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function state(): array
    {
        return session(self::SESSION_KEY, []);
    }

    private function mergeState(string $key, array $value): void
    {
        $state = $this->state();
        $state[$key] = $value;
        $state['updated_at'] = now()->toIso8601String();

        session()->put(self::SESSION_KEY, $state);
    }

    private function resolveAddressPayload(array $payload, ?User $user, string $purpose): array
    {
        if (($payload['address_choice'] ?? 'new') === 'saved' && $user instanceof User) {
            $saved = $user->customerAddresses()
                ->whereKey((int) ($payload['saved_address_id'] ?? 0))
                ->first();

            if ($saved instanceof CustomerAddress) {
                return [
                    'source' => 'saved',
                    'saved_address_id' => $saved->id,
                    'label' => $saved->company_name ?: $saved->formattedName(),
                    'address' => $this->addressArrayFromModel($saved),
                ];
            }
        }

        $address = [
            'first_name' => trim((string) ($payload['first_name'] ?? '')),
            'last_name' => trim((string) ($payload['last_name'] ?? '')),
            'company_name' => trim((string) ($payload['company_name'] ?? '')),
            'address_line_1' => trim((string) ($payload['address_line_1'] ?? '')),
            'address_line_2' => trim((string) ($payload['address_line_2'] ?? '')),
            'city' => trim((string) ($payload['city'] ?? '')),
            'state' => trim((string) ($payload['state'] ?? '')),
            'country' => trim((string) ($payload['country'] ?? 'United States')),
            'postal_code' => trim((string) ($payload['postal_code'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'email' => Str::lower(trim((string) ($payload['email'] ?? ''))),
            'delivery_instruction' => Str::limit(trim((string) ($payload['delivery_instruction'] ?? '')), 800, ''),
        ];

        if ($user instanceof User && (bool) ($payload['save_to_account'] ?? false)) {
            $record = $user->customerAddresses()->create([
                'type' => $purpose === 'billing' ? 'billing' : 'shipping',
                'first_name' => $address['first_name'],
                'last_name' => $address['last_name'],
                'company_name' => $address['company_name'] ?: null,
                'address_line_1' => $address['address_line_1'],
                'address_line_2' => $address['address_line_2'] ?: null,
                'city' => $address['city'],
                'state' => $address['state'] ?: null,
                'country' => $address['country'],
                'postal_code' => $address['postal_code'],
                'phone' => $address['phone'] ?: null,
                'email' => $address['email'] ?: null,
                'is_default' => $user->customerAddresses()->count() === 0,
            ]);

            return [
                'source' => 'new_saved',
                'saved_address_id' => $record->id,
                'label' => $record->company_name ?: $record->formattedName(),
                'address' => $this->addressArrayFromModel($record) + ['delivery_instruction' => $address['delivery_instruction']],
            ];
        }

        return [
            'source' => 'new',
            'saved_address_id' => null,
            'label' => $address['company_name'] ?: trim($address['first_name'] . ' ' . $address['last_name']),
            'address' => $address,
        ];
    }

    private function addressArrayFromModel(CustomerAddress $address): array
    {
        return [
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'company_name' => $address->company_name,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $address->country,
            'postal_code' => $address->postal_code,
            'phone' => $address->phone,
            'email' => $address->email,
        ];
    }

    private function savedAddresses(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return $user->customerAddresses()
            ->latest('is_default')
            ->latest()
            ->get()
            ->map(fn (CustomerAddress $address): array => [
                'id' => $address->id,
                'label' => $address->company_name ?: $address->formattedName(),
                'type' => $address->typeLabel(),
                'is_default' => $address->is_default,
                'lines' => array_filter([
                    $address->formattedName(),
                    $address->company_name,
                    $address->address_line_1,
                    $address->address_line_2,
                    trim($address->city . ', ' . $address->state . ' ' . $address->postal_code),
                    $address->country,
                ]),
            ])
            ->all();
    }

    private function savedPaymentMethods(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return $user->customerPaymentMethods()
            ->latest('is_default')
            ->latest()
            ->get()
            ->map(fn (CustomerPaymentMethod $method): array => [
                'id' => $method->id,
                'label' => $method->nickname ?: $method->maskedLabel(),
                'brand' => strtoupper($method->brand),
                'last_four' => $method->last_four,
                'expiry' => $method->expiryLabel(),
                'is_default' => $method->is_default,
            ])
            ->all();
    }

    private function checkoutSummary(array $cart, array $state): array
    {
        $shippingMethod = $state['shipping_method'] ?? $this->shippingMethods($cart)[0];
        $shippingCost = (float) ($shippingMethod['price'] ?? $cart['shipping']);
        $paymentMethod = $state['payment_method'] ?? ['method' => 'card', 'label' => 'Credit / Debit Card'];
        $total = max(0, $cart['subtotal'] + $cart['customization_total'] - $cart['discount'] + $shippingCost + $cart['tax']);

        return [
            'items' => $cart['items'],
            'quantity' => $cart['quantity'],
            'subtotal' => $cart['subtotal'],
            'customization_total' => $cart['customization_total'],
            'discount' => $cart['discount'],
            'shipping' => round($shippingCost, 2),
            'tax' => $cart['tax'],
            'total' => round($total, 2),
            'shipping_method' => $shippingMethod,
            'payment_method' => $paymentMethod,
        ];
    }

    private function shippingMethods(array $cart): array
    {
        $standard = max(0, (float) $cart['shipping']);

        return [
            [
                'code' => 'standard',
                'title' => 'Standard Shipping',
                'description' => 'Best for regular orders without event deadline pressure.',
                'eta' => 'Estimated after production: 5–7 business days',
                'price' => $standard,
                'display_price' => '$' . number_format($standard, 2),
            ],
            [
                'code' => 'expedited',
                'title' => 'Expedited Shipping',
                'description' => 'Faster delivery after production is complete.',
                'eta' => 'Estimated after production: 2–4 business days',
                'price' => 24.00,
                'display_price' => '$24.00',
            ],
            [
                'code' => 'rush_review',
                'title' => 'Rush Event Delivery Review',
                'description' => 'For event or tournament deadlines. Our team confirms feasibility before production.',
                'eta' => 'Requires support review',
                'price' => 0.00,
                'display_price' => 'Quote',
            ],
            [
                'code' => 'bulk_freight',
                'title' => 'Bulk Freight / Team Shipment',
                'description' => 'Recommended for 50+ items, school orders, league orders, and cartons.',
                'eta' => 'Confirmed by support team',
                'price' => 0.00,
                'display_price' => 'Quote',
            ],
        ];
    }

    private function paymentOptions(): array
    {
        return [
            'card' => [
                'title' => 'Credit / Debit Card',
                'description' => 'Pay securely through a PCI-compliant provider such as Stripe hosted checkout.',
                'badge' => 'Secure',
            ],
            'paypal' => [
                'title' => 'PayPal',
                'description' => 'Use PayPal checkout for eligible online orders.',
                'badge' => 'PayPal',
            ],
            'invoice' => [
                'title' => 'Request Invoice for Bulk Order',
                'description' => 'Best for schools, leagues, businesses, and quote-based custom orders.',
                'badge' => 'Invoice',
            ],
            'saved_card' => [
                'title' => 'Saved Payment Method',
                'description' => 'Use an already saved tokenized payment method from your account.',
                'badge' => 'Saved',
            ],
        ];
    }

    private function steps(): array
    {
        return [
            ['key' => 'information', 'label' => 'Information', 'route' => 'checkout.information'],
            ['key' => 'shipping', 'label' => 'Shipping Address', 'route' => 'checkout.shipping-address'],
            ['key' => 'billing', 'label' => 'Billing', 'route' => 'checkout.billing-address'],
            ['key' => 'shipping_method', 'label' => 'Shipping Method', 'route' => 'checkout.shipping-method'],
            ['key' => 'payment', 'label' => 'Payment', 'route' => 'checkout.payment-method'],
            ['key' => 'review', 'label' => 'Review', 'route' => 'checkout.review'],
            ['key' => 'place', 'label' => 'Place Order', 'route' => 'checkout.place-order'],
        ];
    }
}
