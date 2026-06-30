<?php

namespace App\Services\Checkout;

use App\Models\CustomerAddress;
use App\Models\CustomerPaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Shipping\RuralAreaSurchargeService;
use App\Services\Shipping\ShippingMethodService;
use App\Services\Payments\PaymentMethodService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    private const SESSION_KEY = 'nextplay_checkout';

    public function __construct(
        private readonly CartService $cart,
        private readonly RuralAreaSurchargeService $ruralSurcharges,
        private readonly ShippingMethodService $shipping,
        private readonly PaymentMethodService $payments,
    ) {
    }

    public function pageData(?User $user = null): array
    {
        $cart = $this->cart->summary();
        $state = $this->state();
        $summary = $this->checkoutSummary($cart, $state);
        $paymentOptions = $this->paymentOptions($summary, $user);
        $savedCardGateway = $this->payments->savedCardGateway($paymentOptions);

        return [
            'cart' => $cart,
            'state' => $state,
            'steps' => $this->steps(),
            'savedContact' => $this->savedContact($user),
            'savedAddresses' => $this->savedAddresses($user),
            'savedShippingAddresses' => $this->savedAddresses($user, 'shipping'),
            'savedBillingAddresses' => $this->savedAddresses($user, 'billing'),
            'savedPaymentMethods' => $savedCardGateway ? $this->savedPaymentMethods($user) : [],
            'savedCardGateway' => $savedCardGateway,
            'shippingMethods' => $this->shippingMethods($cart, $state),
            'paymentOptions' => $paymentOptions,
            'summary' => $summary,
            'orderIdempotencyKey' => $this->orderIdempotencyKey(),
        ];
    }

    public function hasCheckoutItems(): bool
    {
        return ! $this->cart->summary()['is_empty'];
    }

    public function firstIncompleteStepBefore(string $currentStep): ?array
    {
        $stepKeys = collect($this->steps())->pluck('key')->values()->all();
        $currentIndex = array_search($currentStep, $stepKeys, true);

        if ($currentIndex === false || $currentIndex === 0) {
            return null;
        }

        foreach (array_slice($stepKeys, 0, $currentIndex) as $stepKey) {
            if (! $this->isStepComplete($stepKey)) {
                return [
                    'key' => $stepKey,
                    'route' => $this->stepRoute($stepKey),
                    'message' => $this->stepMessage($stepKey),
                ];
            }
        }

        return null;
    }

    public function validateReadyToPlaceOrder(?User $user = null): void
    {
        if (! $user instanceof User || ! $user->isCustomer()) {
            throw ValidationException::withMessages([
                'checkout' => 'Please sign in with a customer account before continuing checkout.',
            ]);
        }

        if (! $this->hasCheckoutItems()) {
            throw ValidationException::withMessages([
                'checkout' => 'Add at least one product to your cart before checkout.',
            ]);
        }

        $missingStep = $this->firstIncompleteStepBefore('place');

        if ($missingStep !== null) {
            throw ValidationException::withMessages([
                'checkout' => $missingStep['message'],
            ]);
        }
    }

    public function storeInformation(array $payload, ?User $user = null): void
    {
        if (($payload['contact_choice'] ?? 'new') === 'saved' && $user instanceof User) {
            $saved = $this->savedContact($user);

            if (! ($saved['is_complete'] ?? false)) {
                throw ValidationException::withMessages([
                    'contact_choice' => 'Your saved contact is incomplete. Please enter the missing details once and save them to your account.',
                ]);
            }

            $information = [
                'source' => 'saved',
                'email' => $saved['email'],
                'phone' => $saved['phone'],
                'first_name' => $saved['first_name'],
                'last_name' => $saved['last_name'],
                'order_note' => Str::limit(trim((string) ($payload['order_note'] ?? '')), 1200, ''),
            ];
        } else {
            $information = [
                'source' => 'new',
                'email' => Str::lower(trim((string) $payload['email'])),
                'phone' => trim((string) $payload['phone']),
                'first_name' => trim((string) $payload['first_name']),
                'last_name' => trim((string) $payload['last_name']),
                'order_note' => Str::limit(trim((string) ($payload['order_note'] ?? '')), 1200, ''),
            ];

            if ($user instanceof User && (bool) ($payload['save_to_account'] ?? false)) {
                $user->forceFill([
                    'name' => trim($information['first_name'].' '.$information['last_name']) ?: $user->name,
                    'phone' => $information['phone'],
                ])->save();

                $information['source'] = 'new_saved';
            }
        }

        $this->mergeState('information', $information);
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
        $methods = collect($this->shippingMethods($this->cart->summary(), $this->state()))->keyBy('code');
        $method = $methods->get($methodCode);

        if (! is_array($method)) {
            throw ValidationException::withMessages([
                'shipping_method' => 'Please choose an available shipping method for this cart and shipping address.',
            ]);
        }

        $this->mergeState('shipping_method', $method);
    }

    public function storePaymentMethod(array $payload, ?User $user = null): void
    {
        $state = $this->state();
        $cart = $this->cart->summary();
        $summary = $this->checkoutSummary($cart, $state);
        $availableMethods = $this->paymentOptions($summary, $user);
        $methodsByCode = collect($availableMethods)->keyBy('code');
        $method = (string) $payload['payment_method'];
        $savedPaymentMethodId = null;
        $display = null;

        if ($method === 'saved_card') {
            $savedGateway = $this->payments->savedCardGateway($availableMethods);

            if (! $savedGateway) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Saved payment methods are not available for this order total.',
                ]);
            }

            if (! $user instanceof User) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Please sign in before using a saved payment method.',
                ]);
            }

            $saved = $user->customerPaymentMethods()->whereKey((int) ($payload['saved_payment_method_id'] ?? 0))->first();

            if (! $saved instanceof CustomerPaymentMethod) {
                throw ValidationException::withMessages([
                    'saved_payment_method_id' => 'Please choose a valid saved payment method from your account.',
                ]);
            }

            $savedPaymentMethodId = $saved->id;
            $display = [
                'brand' => $saved->brand,
                'last_four' => $saved->last_four,
                'expiry' => $saved->expiryLabel(),
            ];

            $payment = array_merge($savedGateway, [
                'method' => 'saved_card',
                'code' => (string) $savedGateway['code'],
                'label' => $saved->maskedLabel(),
                'gateway_label' => $savedGateway['label'] ?? $savedGateway['title'] ?? 'Saved card',
                'saved_payment_method_id' => $savedPaymentMethodId,
                'display' => $display,
                'amount' => round((float) ($summary['total'] ?? 0), 2),
                'display_amount' => '$'.number_format((float) ($summary['total'] ?? 0), 2),
            ]);
        } else {
            $selected = $methodsByCode->get($method);

            if (! is_array($selected)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Please choose an available payment method for this order total.',
                ]);
            }

            $payment = array_merge($selected, [
                'method' => (string) $selected['code'],
                'label' => $selected['label'] ?? $selected['title'] ?? 'Payment method',
                'saved_payment_method_id' => null,
                'display' => [
                    'note' => $selected['instructions'] ?: ($selected['requires_provider_redirect']
                        ? 'Payment details will be collected by the secure payment provider.'
                        : 'Admin will review payment details for this method.'),
                ],
                'amount' => round((float) ($summary['total'] ?? 0), 2),
                'display_amount' => '$'.number_format((float) ($summary['total'] ?? 0), 2),
            ]);
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
        $this->validateReadyToPlaceOrder($user);

        $state = $this->state();
        $cart = $this->cart->summary();
        $summary = $this->checkoutSummary($cart, $state);
        $idempotencyKey = trim((string) ($payload['idempotency_key'] ?? ''));
        $customerEmail = (string) ($state['information']['email'] ?? $user?->email ?? '');
        $couponValidation = $this->cart->appliedCouponValidation($customerEmail, $user);
        $this->validateSelectedPaymentMethod($summary, $user);

        if ($couponValidation !== null && ! ($couponValidation['valid'] ?? false)) {
            throw ValidationException::withMessages([
                'coupon_code' => $couponValidation['message'] ?? 'The selected promo code is no longer valid for this order.',
            ]);
        }

        $existingSnapshot = $state['placed_order'] ?? null;
        if (is_array($existingSnapshot) && hash_equals((string) ($existingSnapshot['idempotency_key'] ?? ''), $idempotencyKey)) {
            return $existingSnapshot;
        }

        $existingOrder = Order::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existingOrder instanceof Order) {
            $snapshot = $this->orderSnapshot($existingOrder->load('items'));
            $this->mergeState('placed_order', $snapshot);
            return $snapshot;
        }

        try {
            $order = DB::transaction(function () use ($state, $cart, $summary, $idempotencyKey, $user, $couponValidation): Order {
                $paymentMethod = (string) ($summary['payment_method']['method'] ?? '');
                $paymentRequiresManualReview = (bool) ($summary['payment_method']['requires_manual_review'] ?? false);
                $paymentType = (string) ($summary['payment_method']['payment_type'] ?? '');
                $isManualPaymentFlow = $paymentRequiresManualReview || in_array($paymentType, ['invoice', 'bank_transfer', 'manual'], true);

                $order = Order::create([
                    'user_id' => $user?->id,
                    'order_number' => $this->uniqueOrderNumber(),
                    'status' => $isManualPaymentFlow ? 'quote_invoice_requested' : 'pending_payment',
                    'payment_status' => 'pending',
                    'fulfillment_status' => 'unfulfilled',
                    'currency' => 'USD',
                    'coupon_id' => $couponValidation['coupon_id'] ?? null,
                    'coupon_code' => $couponValidation['code'] ?? null,
                    'coupon_snapshot' => $couponValidation['snapshot'] ?? null,
                    'customer_email' => $state['information']['email'] ?? $user?->email ?? '',
                    'customer_name' => trim(($state['information']['first_name'] ?? '') . ' ' . ($state['information']['last_name'] ?? '')) ?: ($user?->name ?? 'Customer'),
                    'customer_phone' => $state['information']['phone'] ?? null,
                    'subtotal' => $summary['subtotal'],
                    'customization_total' => $summary['customization_total'],
                    'discount_total' => $summary['discount'],
                    'shipping_total' => $summary['shipping'],
                    'rural_surcharge_total' => $summary['rural_surcharge'],
                    'product_shipping_total' => $summary['product_shipping_total'] ?? 0,
                    'tax_total' => $summary['tax'],
                    'grand_total' => $summary['total'],
                    'total_quantity' => $summary['quantity'],
                    'information' => $state['information'] ?? [],
                    'shipping_address' => $state['shipping_address'] ?? [],
                    'billing_address' => $state['billing_address'] ?? [],
                    'shipping_method' => $summary['shipping_method'] ?? ($state['shipping_method'] ?? []),
                    'payment_method' => $summary['payment_method'],
                    'customer_note' => $state['information']['order_note'] ?? null,
                    'idempotency_key' => $idempotencyKey,
                    'placed_at' => now(),
                ]);

                foreach ($cart['items'] as $cartItem) {
                    $productData = (array) ($cartItem['product'] ?? []);
                    $product = Product::query()->where('slug', $cartItem['product_slug'] ?? $productData['slug'] ?? '')->first();
                    $order->items()->create([
                        'product_id' => $product?->id,
                        'product_slug' => $cartItem['product_slug'] ?? $productData['slug'] ?? null,
                        'product_name' => $productData['title'] ?? $productData['short_title'] ?? 'Custom Product',
                        'sku' => $productData['sku'] ?? $product?->sku,
                        'image_url' => $productData['image'] ?? null,
                        'quantity' => (int) ($cartItem['quantity'] ?? 1),
                        'unit_price' => (float) ($cartItem['unit_price'] ?? 0),
                        'customization_unit_price' => (float) ($cartItem['customization_unit_price'] ?? 0),
                        'line_total' => (float) ($cartItem['line_total'] ?? 0),
                        'customization' => $cartItem['customization'] ?? [],
                        'is_digital' => false,
                    ]);
                }

                $order->histories()->create([
                    'actor_id' => $user?->id,
                    'status' => $order->status,
                    'title' => 'Order placed',
                    'description' => $isManualPaymentFlow
                        ? 'The order was submitted for manual payment review.'
                        : 'The order was created and is waiting for secure payment confirmation.',
                    'occurred_at' => now(),
                ]);

                return $order->load('items');
            });
        } catch (QueryException $exception) {
            $order = Order::query()
                ->where('idempotency_key', $idempotencyKey)
                ->with('items')
                ->first();

            if (! $order) {
                throw $exception;
            }
        }

        $this->cart->recordCouponRedemption($order, (float) $order->discount_total, $user);

        $snapshot = $this->orderSnapshot($order);
        $this->mergeState('placed_order', $snapshot);
        $this->cart->clear(true);

        Log::info('Checkout order persisted', [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'user_id' => $order->user_id,
        ]);

        return $snapshot;
    }

    private function orderSnapshot(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'idempotency_key' => $order->idempotency_key,
            'user_id' => $order->user_id,
            'customer_email' => $order->customer_email,
            'customer_name' => $order->customer_name,
            'items' => $order->items->map(fn ($item): array => [
                'product' => [
                    'title' => $item->product_name,
                    'image' => $item->image_url,
                    'alt' => $item->product_name,
                    'slug' => $item->product_slug,
                    'sku' => $item->sku,
                ],
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'customization_unit_price' => (float) $item->customization_unit_price,
                'line_total' => (float) $item->line_total,
                'customization' => $item->customization ?? [],
            ])->all(),
            'totals' => [
                'subtotal' => (float) $order->subtotal,
                'customization_total' => (float) $order->customization_total,
                'discount' => (float) $order->discount_total,
                'coupon_code' => $order->coupon_code,
                'shipping' => (float) $order->shipping_total,
                'rural_surcharge' => (float) $order->rural_surcharge_total,
                'product_shipping_total' => (float) ($order->product_shipping_total ?? 0),
                'tax' => (float) $order->tax_total,
                'total' => (float) $order->grand_total,
                'quantity' => $order->total_quantity,
            ],
            'information' => $order->information ?? [],
            'shipping_address' => $order->shipping_address ?? [],
            'billing_address' => $order->billing_address ?? [],
            'shipping_method' => $order->shipping_method ?? [],
            'payment_method' => $order->payment_method ?? [],
            'placed_at' => $order->placed_at?->toIso8601String(),
            'next_step' => $order->status === 'quote_invoice_requested' ? 'admin_invoice_review' : 'payment_provider_redirect',
        ];
    }

    private function uniqueOrderNumber(): string
    {
        do {
            $number = 'NP-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    public function orderIdempotencyKey(): string
    {
        $state = $this->state();
        $key = (string) ($state['order_idempotency_key'] ?? '');

        if (isset($state['placed_order']) || strlen($key) !== 40) {
            unset($state['placed_order']);
            $key = Str::random(40);
            $state['order_idempotency_key'] = $key;
            session()->put(self::SESSION_KEY, $state);
        }

        return $key;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function state(): array
    {
        $state = session(self::SESSION_KEY, []);

        if (isset($state['information']) && is_array($state['information'])) {
            $information = Arr::only($state['information'], [
                'email',
                'phone',
                'first_name',
                'last_name',
                'order_note',
                'source',
            ]);

            if ($information !== $state['information']) {
                $state['information'] = $information;
                session()->put(self::SESSION_KEY, $state);
            }
        }

        return $state;
    }

    private function mergeState(string $key, array $value): void
    {
        $state = $this->state();

        if ($key !== 'placed_order' && isset($state['placed_order'])) {
            unset($state['placed_order']);
            $state['order_idempotency_key'] = Str::random(40);
        }

        $this->forgetDependentSteps($state, $key);

        $state[$key] = $value;
        $state['updated_at'] = now()->toIso8601String();

        session()->put(self::SESSION_KEY, $state);
    }

    private function forgetDependentSteps(array &$state, string $key): void
    {
        $dependencies = [
            'information' => ['shipping_address', 'billing_address', 'shipping_method', 'payment_method', 'review'],
            'shipping_address' => ['billing_address', 'shipping_method', 'payment_method', 'review'],
            'billing_address' => ['shipping_method', 'payment_method', 'review'],
            'shipping_method' => ['payment_method', 'review'],
            'payment_method' => ['review'],
        ];

        foreach ($dependencies[$key] ?? [] as $dependentKey) {
            unset($state[$dependentKey]);
        }
    }

    private function resolveAddressPayload(array $payload, ?User $user, string $purpose): array
    {
        if (($payload['address_choice'] ?? 'new') === 'saved' && $user instanceof User) {
            $saved = $user->customerAddresses()
                ->whereKey((int) ($payload['saved_address_id'] ?? 0))
                ->whereIn('type', [$purpose, 'both'])
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
            $type = $purpose === 'billing' ? 'billing' : 'shipping';
            $makeDefault = $user->customerAddresses()->whereIn('type', [$type, 'both'])->where('is_default', true)->doesntExist();

            if ($makeDefault) {
                $user->customerAddresses()->whereIn('type', [$type, 'both'])->update(['is_default' => false]);
            }

            $record = $user->customerAddresses()->create([
                'type' => $type,
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
                'delivery_instruction' => $address['delivery_instruction'] ?: null,
                'is_default' => $makeDefault,
            ]);

            return [
                'source' => 'new_saved',
                'saved_address_id' => $record->id,
                'label' => $record->company_name ?: $record->formattedName(),
                'address' => $this->addressArrayFromModel($record),
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
            'delivery_instruction' => $address->delivery_instruction,
        ];
    }

    private function savedContact(?User $user): array
    {
        if (! $user instanceof User) {
            return ['is_complete' => false];
        }

        [$firstName, $lastName] = $this->splitName((string) $user->name);

        return [
            'is_complete' => filled($user->email) && filled($user->phone) && filled($firstName) && filled($lastName),
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'label' => trim($firstName.' '.$lastName) ?: $user->email,
        ];
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function savedAddresses(?User $user, ?string $purpose = null): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $query = $user->customerAddresses();

        if (in_array($purpose, ['shipping', 'billing'], true)) {
            $query->whereIn('type', [$purpose, 'both']);
        }

        return $query
            ->latest('is_default')
            ->latest()
            ->get()
            ->map(fn (CustomerAddress $address): array => [
                'id' => $address->id,
                'label' => $address->company_name ?: $address->formattedName(),
                'type' => $address->typeLabel(),
                'type_raw' => $address->type,
                'is_default' => $address->is_default,
                'lines' => array_filter([
                    $address->formattedName(),
                    $address->company_name,
                    $address->address_line_1,
                    $address->address_line_2,
                    trim($address->city . ', ' . $address->state . ' ' . $address->postal_code),
                    $address->country,
                    $address->phone ? 'Phone: '.$address->phone : null,
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

    private function isStepComplete(string $stepKey): bool
    {
        $state = $this->state();

        return match ($stepKey) {
            'information' => filled(Arr::get($state, 'information.email'))
                && filled(Arr::get($state, 'information.phone'))
                && filled(Arr::get($state, 'information.first_name'))
                && filled(Arr::get($state, 'information.last_name')),
            'shipping' => filled(Arr::get($state, 'shipping_address.address.first_name'))
                && filled(Arr::get($state, 'shipping_address.address.last_name'))
                && filled(Arr::get($state, 'shipping_address.address.address_line_1'))
                && filled(Arr::get($state, 'shipping_address.address.city'))
                && filled(Arr::get($state, 'shipping_address.address.postal_code'))
                && filled(Arr::get($state, 'shipping_address.address.country')),
            'billing' => filled(Arr::get($state, 'billing_address.address.address_line_1'))
                || (bool) Arr::get($state, 'billing_address.same_as_shipping'),
            'shipping_method' => filled(Arr::get($state, 'shipping_method.code')),
            'payment' => filled(Arr::get($state, 'payment_method.method')),
            'review' => (bool) Arr::get($state, 'review.confirmed'),
            default => true,
        };
    }

    private function stepRoute(string $stepKey): string
    {
        return (string) (Arr::get(collect($this->steps())->firstWhere('key', $stepKey), 'route') ?: 'checkout.information');
    }

    private function stepMessage(string $stepKey): string
    {
        return match ($stepKey) {
            'information' => 'Please complete your contact information before continuing checkout.',
            'shipping' => 'Please complete your shipping address before continuing checkout.',
            'billing' => 'Please complete your billing address before continuing checkout.',
            'shipping_method' => 'Please select a shipping method before continuing checkout.',
            'payment' => 'Please select a secure payment method before continuing checkout.',
            'review' => 'Please review and confirm your order details before placing the order.',
            default => 'Please complete the previous checkout step before continuing.',
        };
    }

    private function checkoutSummary(array $cart, array $state): array
    {
        $methods = collect($this->shippingMethods($cart, $state))->keyBy('code');
        $selectedCode = (string) Arr::get($state, 'shipping_method.code', '');
        $shippingMethod = $selectedCode !== '' ? $methods->get($selectedCode) : null;
        $shippingMethod = $shippingMethod ?? $this->shipping->defaultMethod($methods->values()->all()) ?? [];

        $selectedShippingPrice = (float) ($shippingMethod['price'] ?? 0);
        $ruralSurcharge = (float) ($shippingMethod['rural_surcharge']['amount'] ?? 0);
        $productShipping = $this->shipping->productShippingTotal($cart);
        $productShippingTotal = (float) ($productShipping['total'] ?? 0);
        $paymentMethod = $state['payment_method'] ?? [
            'method' => null,
            'code' => null,
            'label' => 'Payment method not selected',
            'amount' => null,
        ];

        // Product-specific shipping selected on the product page is already included in
        // the configured item price by CartService. We keep it visible here so admins
        // and customers can see that it was carried automatically without charging it twice.
        $total = round(max(0, $cart['subtotal'] + $cart['customization_total'] - $cart['discount'] + $selectedShippingPrice + $cart['tax']), 2);

        if (is_array($paymentMethod) && filled($paymentMethod['method'] ?? null)) {
            $paymentMethod['amount'] = $total;
            $paymentMethod['display_amount'] = '$'.number_format($total, 2);
        }

        return [
            'items' => $cart['items'],
            'quantity' => $cart['quantity'],
            'subtotal' => $cart['subtotal'],
            'customization_total' => $cart['customization_total'],
            'discount' => $cart['discount'],
            'coupon_code' => $cart['coupon_code'] ?? null,
            'shipping_base' => round((float) ($shippingMethod['base_price'] ?? max(0, $selectedShippingPrice - $ruralSurcharge)), 2),
            'rural_surcharge' => round($ruralSurcharge, 2),
            'rural_surcharge_details' => $shippingMethod['rural_surcharge'] ?? null,
            'product_shipping_total' => round($productShippingTotal, 2),
            'product_shipping_lines' => $productShipping['lines'] ?? [],
            'shipping' => round($selectedShippingPrice, 2),
            'tax' => $cart['tax'],
            'total' => $total,
            'shipping_method' => $shippingMethod,
            'payment_method' => $paymentMethod,
        ];
    }

    private function shippingMethods(array $cart, array $state = []): array
    {
        $address = (array) Arr::get($state, 'shipping_address.address', []);
        $surcharge = $this->ruralSurchargeForState($state);

        return $this->shipping->availableMethods($cart, $address, $surcharge);
    }

    private function ruralSurchargeForState(array $state): ?array
    {
        $address = (array) Arr::get($state, 'shipping_address.address', []);

        return $this->ruralSurcharges->resolve(
            $address['postal_code'] ?? null,
            $address['country'] ?? 'United States',
            $address['state'] ?? null,
        );
    }

    private function paymentOptions(array $summary, ?User $user = null): array
    {
        return $this->payments->availableMethods($summary, $user);
    }

    private function validateSelectedPaymentMethod(array $summary, ?User $user = null): void
    {
        $payment = (array) ($summary['payment_method'] ?? []);
        $method = (string) ($payment['method'] ?? '');
        $availableMethods = $this->paymentOptions($summary, $user);

        if ($method === '') {
            throw ValidationException::withMessages([
                'payment_method' => 'Please choose a payment method before placing the order.',
            ]);
        }

        if ($method === 'saved_card') {
            $savedGateway = $this->payments->savedCardGateway($availableMethods);
            $savedPaymentMethodId = (int) ($payment['saved_payment_method_id'] ?? 0);

            if (! $savedGateway || ! $user instanceof User || $savedPaymentMethodId < 1) {
                throw ValidationException::withMessages([
                    'payment_method' => 'The selected saved payment method is no longer available.',
                ]);
            }

            $exists = $user->customerPaymentMethods()->whereKey($savedPaymentMethodId)->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'payment_method' => 'The selected saved payment method does not belong to your account.',
                ]);
            }

            return;
        }

        $available = collect($availableMethods)->contains(fn (array $option): bool => (string) ($option['code'] ?? '') === $method);

        if (! $available) {
            throw ValidationException::withMessages([
                'payment_method' => 'The selected payment method is no longer available for this order total.',
            ]);
        }
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
