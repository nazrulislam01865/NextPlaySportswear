<?php

namespace App\Services\Cart;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartItem;
use App\Models\User;
use App\Services\Discounts\CouponService;
use App\Services\Storefront\ProductCatalogService;
use App\Support\PriceTableShipping;
use App\Support\ProductRoster;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CartService
{
    private const SESSION_ITEMS_KEY = 'nextplay_cart.items';
    private const SESSION_COUPON_KEY = 'nextplay_cart.coupon_code';

    private ?bool $databaseCartIsAvailable = null;

    public function __construct(
        private readonly ProductCatalogService $products,
        private readonly CouponService $coupons,
    ) {
    }

    public function summary(bool $preview = false): array
    {
        $items = $preview ? $this->previewItems() : $this->items();
        $couponCode = $preview ? 'TEAM10' : $this->couponCode();

        $subtotal = (float) collect($items)->sum('line_subtotal');
        // Customization contains only genuine product customization and production
        // surcharges. Product-level shipping is kept in its own total.
        $customizationTotal = (float) collect($items)->sum('customization_total');
        $merchandiseTotal = $subtotal + $customizationTotal;
        $quantity = (int) collect($items)->sum('quantity');
        $couponValidation = $couponCode
            ? $this->coupons->validateForCart($couponCode, (float) $merchandiseTotal, $quantity, $this->currentUser())
            : null;
        $discount = $couponValidation['valid'] ?? false ? (float) $couponValidation['discount'] : 0.00;
        $genericShippingItems = collect($items)->where('uses_product_shipping', false);
        $additionalShipping = $this->calculateShipping(
            (float) $genericShippingItems->sum(fn (array $item) => ($item['line_subtotal'] ?? 0) + ($item['customization_total'] ?? 0)),
            (int) $genericShippingItems->sum('quantity')
        );
        $productShippingTotal = (float) collect($items)->sum('product_shipping_total');
        $shipping = $additionalShipping + $productShippingTotal;
        $tax = $this->calculateTax($merchandiseTotal - $discount);
        $total = max(0, $merchandiseTotal - $discount + $shipping + $tax);

        return [
            'items' => array_values($items),
            'coupon_code' => ($couponValidation['valid'] ?? false) ? $couponCode : null,
            'coupon_error' => ($couponCode && ! ($couponValidation['valid'] ?? false)) ? ($couponValidation['message'] ?? 'This promo code is not valid for the current cart.') : null,
            'coupon_message' => ($couponValidation['valid'] ?? false) ? ($couponValidation['message'] ?? 'Promo code applied successfully.') : null,
            'coupon_id' => $couponValidation['coupon_id'] ?? null,
            'coupon_snapshot' => $couponValidation['snapshot'] ?? null,
            'subtotal' => round($subtotal, 2),
            'customization_total' => round($customizationTotal, 2),
            'merchandise_total' => round($merchandiseTotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shipping, 2),
            'additional_shipping' => round($additionalShipping, 2),
            'product_shipping_total' => round($productShippingTotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'quantity' => $quantity,
            'is_empty' => count($items) === 0,
            'is_preview' => $preview,
            'checkout_ready' => count($items) > 0,
            'trust_points' => $this->trustPoints(),
            'payment_badges' => ['Visa', 'Mastercard', 'PayPal', 'Stripe', 'Bank Transfer'],
        ];
    }

    public function items(): array
    {
        if (! $this->databaseCartAvailable()) {
            return $this->sessionItems();
        }

        $cart = $this->currentCart($this->hasLegacySessionCart());

        if (! $cart instanceof ShoppingCart) {
            return [];
        }

        $this->importLegacySessionCart($cart);

        return $cart->items()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ShoppingCartItem $item): array => $this->cartItemArray($item))
            ->filter()
            ->values()
            ->all();
    }

    public function findItem(string $key): ?array
    {
        if (! $this->databaseCartAvailable()) {
            return collect($this->sessionItems())
                ->first(fn (array $item): bool => hash_equals((string) ($item['key'] ?? ''), $key));
        }

        $cart = $this->currentCart($this->hasLegacySessionCart());

        if (! $cart instanceof ShoppingCart) {
            return null;
        }

        $this->importLegacySessionCart($cart);

        $record = $cart->items()->where('item_key', $key)->first();

        return $record instanceof ShoppingCartItem ? $this->cartItemArray($record) : null;
    }

    public function count(): int
    {
        $legacyQuantity = (int) collect((array) session(self::SESSION_ITEMS_KEY, []))
            ->sum(fn (mixed $item): int => max(0, (int) data_get($item, 'quantity', 0)));

        if (! $this->databaseCartAvailable()) {
            return $legacyQuantity;
        }

        $cart = $this->currentCart(false);

        if (! $cart instanceof ShoppingCart) {
            return $legacyQuantity;
        }

        return (int) $cart->items()->sum('quantity') + $legacyQuantity;
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

        $item = $this->repriceItem([
            'key' => $key,
            'product_slug' => $product['slug'],
            'quantity' => $quantity,
            'customization' => $customization,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $this->databaseCartAvailable()) {
            return $this->storeSessionItem($item, true);
        }

        $cart = $this->currentCart(true);
        abort_unless($cart instanceof ShoppingCart, 500, 'Unable to create a shopping cart.');

        $this->importLegacySessionCart($cart);
        $this->persistCartItem($cart, $item, true);
        $this->touchCart($cart);

        return $this->summary();
    }

    public function replace(string $key, array $payload): array
    {
        $existing = $this->findItem($key);

        abort_if($existing === null, 404, 'The cart item you are trying to edit no longer exists.');
        abort_unless(
            hash_equals((string) ($existing['product_slug'] ?? ''), (string) ($payload['product_slug'] ?? '')),
            422,
            'The selected product does not match this cart item.'
        );

        $product = $this->products->findBySlug((string) $payload['product_slug']);

        abort_if($product === null, 404);

        $customization = $this->sanitizeCustomization($payload, $product, (int) ($payload['quantity'] ?? 1));
        $configuredQuantity = (int) collect($customization['configuration']['quantities'] ?? [])->sum();
        $quantity = $this->sanitizeQuantity($configuredQuantity > 0 ? $configuredQuantity : (int) ($payload['quantity'] ?? 1), $product);
        $this->validateRequiredConfiguration($product, $customization);
        $newKey = $this->makeItemKey($product['slug'], $customization);

        $item = $this->repriceItem([
            'key' => $newKey,
            'product_slug' => $product['slug'],
            'quantity' => $quantity,
            'customization' => $customization,
            'created_at' => $existing['created_at'] ?? now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $this->databaseCartAvailable()) {
            $items = $this->sessionItems();
            $existingIndex = collect($items)->search(
                fn (array $cartItem): bool => hash_equals((string) ($cartItem['key'] ?? ''), $key)
            );

            abort_if($existingIndex === false, 404, 'The cart item you are trying to edit no longer exists.');

            $duplicateExists = collect($items)->contains(function (array $cartItem, int $index) use ($existingIndex, $newKey): bool {
                return $index !== $existingIndex
                    && hash_equals((string) ($cartItem['key'] ?? ''), $newKey);
            });

            abort_if($duplicateExists, 422, 'Another cart item already uses these exact options. Choose a different configuration or remove the duplicate item first.');

            $items[$existingIndex] = $item;
            session()->put(self::SESSION_ITEMS_KEY, array_values($items));

            return $this->summary();
        }

        $cart = $this->currentCart($this->hasLegacySessionCart());
        abort_unless($cart instanceof ShoppingCart, 404, 'The cart item you are trying to edit no longer exists.');

        $this->importLegacySessionCart($cart);
        $record = $cart->items()->where('item_key', $key)->first();
        abort_unless($record instanceof ShoppingCartItem, 404, 'The cart item you are trying to edit no longer exists.');

        $duplicateExists = $cart->items()
            ->where('item_key', $newKey)
            ->where($record->getKeyName(), '!=', $record->getKey())
            ->exists();

        abort_if($duplicateExists, 422, 'Another cart item already uses these exact options. Choose a different configuration or remove the duplicate item first.');

        $this->persistCalculatedCartItem($record, $item);
        $this->touchCart($cart);

        return $this->summary();
    }

    public function update(string $key, int $quantity): array
    {
        if (! $this->databaseCartAvailable()) {
            return $this->updateSessionItem($key, $quantity);
        }

        $cart = $this->currentCart(false);

        if (! $cart instanceof ShoppingCart) {
            return $this->summary();
        }

        $record = $cart->items()->where('item_key', $key)->first();

        if ($record instanceof ShoppingCartItem) {
            $product = $this->products->findBySlug((string) $record->product_slug);
            $quantity = $product ? $this->sanitizeQuantity($quantity, $product) : max(1, $quantity);
            $record->quantity = $quantity;
            if ($product) {
                $record->customization = $this->applyCartQuantityToCustomization((array) ($record->customization ?? []), $quantity);
            }
            $record->save();
            $this->persistCalculatedCartItem($record, $this->cartItemArray($record));
            $this->touchCart($cart);
        }

        return $this->summary();
    }

    public function remove(string $key): array
    {
        if (! $this->databaseCartAvailable()) {
            return $this->removeSessionItem($key);
        }

        $cart = $this->currentCart(false);

        if ($cart instanceof ShoppingCart) {
            $cart->items()->where('item_key', $key)->delete();
            $this->touchCart($cart);
        }

        return $this->summary();
    }

    public function applyCoupon(?string $code): array
    {
        $code = $this->coupons->normalizeCode($code);
        $items = $this->items();
        $subtotal = (float) collect($items)->sum('line_subtotal');
        $customizationTotal = (float) collect($items)->sum('customization_total');
        $quantity = (int) collect($items)->sum('quantity');
        $validation = $this->coupons->validateForCart($code, $subtotal + $customizationTotal, $quantity, $this->currentUser());

        if (! ($validation['valid'] ?? false)) {
            $summary = $this->summary();
            $summary['coupon_error'] = $validation['message'] ?? 'This promo code is not valid for the current cart.';
            $summary['coupon_message'] = null;
            return $summary;
        }

        if (! $this->databaseCartAvailable()) {
            session()->put(self::SESSION_COUPON_KEY, $code);
            $summary = $this->summary();
            $summary['coupon_message'] = $validation['message'] ?? 'Promo code applied successfully.';
            $summary['coupon_error'] = null;
            return $summary;
        }

        $cart = $this->currentCart(true);
        abort_unless($cart instanceof ShoppingCart, 500, 'Unable to create a shopping cart.');

        $this->importLegacySessionCart($cart);
        $cart->forceFill([
            'coupon_code' => $code,
            'coupon_id' => $validation['coupon_id'] ?? null,
            'last_activity_at' => now(),
        ])->save();

        $summary = $this->summary();
        $summary['coupon_message'] = $validation['message'] ?? 'Promo code applied successfully.';
        $summary['coupon_error'] = null;

        return $summary;
    }

    public function removeCoupon(): array
    {
        if (! $this->databaseCartAvailable()) {
            session()->forget(self::SESSION_COUPON_KEY);

            return $this->summary();
        }

        $cart = $this->currentCart(false);

        if ($cart instanceof ShoppingCart) {
            $cart->forceFill([
                'coupon_code' => null,
                'coupon_id' => null,
                'last_activity_at' => now(),
            ])->save();
        }

        return $this->summary();
    }

    public function appliedCouponValidation(?string $customerEmail = null, ?User $user = null): ?array
    {
        $code = $this->couponCode();

        if (! $code) {
            return null;
        }

        $summary = $this->summary();

        return $this->coupons->validateForCart(
            $code,
            (float) ($summary['merchandise_total'] ?? (($summary['subtotal'] ?? 0) + ($summary['customization_total'] ?? 0))),
            (int) ($summary['quantity'] ?? 0),
            $user ?: $this->currentUser(),
            $customerEmail
        );
    }

    public function recordCouponRedemption(Order $order, float $discountAmount, ?User $user = null): void
    {
        $validation = $this->appliedCouponValidation((string) $order->customer_email, $user);

        if (! ($validation['valid'] ?? false) || ! (($validation['coupon'] ?? null) instanceof Coupon)) {
            return;
        }

        $cart = $this->currentCart(false);
        $merchandiseTotal = (float) $order->subtotal + (float) $order->customization_total;

        $this->coupons->recordRedemption(
            $validation['coupon'],
            $order,
            $merchandiseTotal,
            $discountAmount,
            $cart,
            $user
        );
    }

    public function clear(bool $converted = false): void
    {
        if (! $this->databaseCartAvailable()) {
            session()->forget([self::SESSION_ITEMS_KEY, self::SESSION_COUPON_KEY]);

            return;
        }

        $cart = $this->currentCart(false);

        if (! $cart instanceof ShoppingCart) {
            return;
        }

        $cart->update([
            'status' => $converted ? 'converted' : 'cleared',
            'converted_at' => $converted ? now() : null,
            'last_activity_at' => now(),
        ]);
    }

    public function claimSessionCart(User $user): void
    {
        if (! $this->databaseCartAvailable()) {
            return;
        }

        $sessionId = $this->sessionId();

        if ($sessionId === '') {
            return;
        }

        $guestCart = ShoppingCart::query()
            ->with('items')
            ->whereNull('user_id')
            ->where('session_id', $sessionId)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        $userCart = ShoppingCart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if (! $guestCart instanceof ShoppingCart) {
            if ($userCart instanceof ShoppingCart) {
                $userCart->forceFill([
                    'session_id' => $sessionId,
                    'last_activity_at' => now(),
                ])->save();
            }

            return;
        }

        if (! $userCart instanceof ShoppingCart) {
            $guestCart->forceFill([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'last_activity_at' => now(),
            ])->save();

            return;
        }

        if ($guestCart->is($userCart)) {
            $userCart->forceFill(['session_id' => $sessionId, 'last_activity_at' => now()])->save();

            return;
        }

        DB::transaction(function () use ($guestCart, $userCart, $sessionId): void {
            $guestCart->loadMissing('items');

            foreach ($guestCart->items as $item) {
                $this->persistCartItem($userCart, $this->cartItemArray($item), true);
            }

            if (blank($userCart->coupon_code) && filled($guestCart->coupon_code)) {
                $userCart->coupon_code = $guestCart->coupon_code;
                $userCart->coupon_id = $guestCart->coupon_id;
            }

            $userCart->session_id = $sessionId;
            $userCart->last_activity_at = now();
            $userCart->save();

            $guestCart->update([
                'status' => 'merged',
                'last_activity_at' => now(),
            ]);
        });
    }

    private function databaseCartAvailable(): bool
    {
        return $this->databaseCartIsAvailable ??= Schema::hasTable('shopping_carts')
            && Schema::hasTable('shopping_cart_items');
    }

    private function currentCart(bool $create = false): ?ShoppingCart
    {
        $user = auth()->user();
        $user = $user instanceof User ? $user : null;
        $sessionId = $this->sessionId();

        if ($user instanceof User) {
            $cart = ShoppingCart::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('updated_at')
                ->first();

            if (! $cart instanceof ShoppingCart && $sessionId !== '') {
                $guestCart = ShoppingCart::query()
                    ->whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->where('status', 'active')
                    ->latest('updated_at')
                    ->first();

                if ($guestCart instanceof ShoppingCart) {
                    $guestCart->forceFill([
                        'user_id' => $user->id,
                        'last_activity_at' => now(),
                    ])->save();
                    $cart = $guestCart;
                }
            }

            if (! $cart instanceof ShoppingCart && $create) {
                $cart = ShoppingCart::create([
                    'user_id' => $user->id,
                    'session_id' => $sessionId ?: null,
                    'status' => 'active',
                    'currency' => 'USD',
                    'last_activity_at' => now(),
                ]);
            }

            if ($cart instanceof ShoppingCart && $sessionId !== '' && $cart->session_id !== $sessionId) {
                $cart->forceFill([
                    'session_id' => $sessionId,
                    'last_activity_at' => now(),
                ])->save();
            }

            return $cart;
        }

        if ($sessionId === '') {
            return null;
        }

        $cart = ShoppingCart::query()
            ->whereNull('user_id')
            ->where('session_id', $sessionId)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if (! $cart instanceof ShoppingCart && $create) {
            $cart = ShoppingCart::create([
                'session_id' => $sessionId,
                'status' => 'active',
                'currency' => 'USD',
                'last_activity_at' => now(),
            ]);
        }

        return $cart;
    }

    private function cartItemArray(ShoppingCartItem $record): array
    {
        $item = [
            'key' => $record->item_key,
            'product_slug' => $record->product_slug,
            'quantity' => (int) $record->quantity,
            'customization' => $record->customization ?? [],
            'created_at' => $record->created_at?->toIso8601String(),
            'updated_at' => $record->updated_at?->toIso8601String(),
        ];

        $priced = $this->repriceItem($item);

        if ($priced === []) {
            $record->delete();

            return [];
        }

        $this->persistCalculatedCartItem($record, $priced);

        return $priced;
    }

    private function persistCartItem(ShoppingCart $cart, array $item, bool $merge): ShoppingCartItem
    {
        $record = $cart->items()->where('item_key', $item['key'])->first();

        if ($record instanceof ShoppingCartItem && $merge) {
            $product = $this->products->findBySlug((string) $item['product_slug']);
            $record->quantity = $product
                ? $this->sanitizeQuantity(((int) $record->quantity) + ((int) $item['quantity']), $product)
                : ((int) $record->quantity) + ((int) $item['quantity']);
            $record->customization = $item['customization'] ?? [];
            $record->updated_at = now();
            $record->save();

            return $this->persistCalculatedCartItem($record, $this->cartItemArray($record));
        }

        if (! $record instanceof ShoppingCartItem) {
            $record = new ShoppingCartItem([
                'shopping_cart_id' => $cart->id,
                'product_slug' => $item['product_slug'],
                'item_key' => $item['key'],
                'quantity' => (int) $item['quantity'],
                'customization' => $item['customization'] ?? [],
            ]);
            $record->shopping_cart_id = $cart->id;
        }

        return $this->persistCalculatedCartItem($record, $item);
    }

    private function persistCalculatedCartItem(ShoppingCartItem $record, array $item): ShoppingCartItem
    {
        if ($item === []) {
            return $record;
        }

        $product = (array) ($item['product'] ?? []);

        $record->forceFill([
            'product_id' => $product['id'] ?? null,
            'product_slug' => $item['product_slug'] ?? $product['slug'] ?? $record->product_slug,
            'item_key' => $item['key'] ?? $record->item_key,
            'quantity' => (int) ($item['quantity'] ?? $record->quantity),
            'unit_price' => (float) ($item['unit_price'] ?? 0),
            'customization_unit_price' => (float) ($item['customization_unit_price'] ?? 0),
            'line_subtotal' => (float) ($item['line_subtotal'] ?? 0),
            'customization_total' => (float) ($item['customization_total'] ?? 0),
            'line_total' => (float) ($item['line_total'] ?? 0),
            'customization' => $item['customization'] ?? [],
            'product_snapshot' => Arr::only($product, ['id', 'slug', 'title', 'short_title', 'summary', 'sku', 'category', 'sport', 'image', 'alt', 'url', 'base_price', 'price']),
        ]);

        $record->saveQuietly();

        return $record;
    }

    private function touchCart(ShoppingCart $cart): void
    {
        $cart->forceFill(['last_activity_at' => now()])->save();
    }

    private function couponCode(): ?string
    {
        if (! $this->databaseCartAvailable()) {
            return session(self::SESSION_COUPON_KEY) ?: null;
        }

        $cart = $this->currentCart($this->hasLegacySessionCart());

        if (! $cart instanceof ShoppingCart) {
            return null;
        }

        $this->importLegacySessionCart($cart);

        return $cart->coupon_code ?: null;
    }

    private function importLegacySessionCart(ShoppingCart $cart): void
    {
        if (! $this->hasLegacySessionCart()) {
            return;
        }

        foreach (session(self::SESSION_ITEMS_KEY, []) as $item) {
            $priced = $this->repriceItem((array) $item);

            if ($priced !== []) {
                $this->persistCartItem($cart, $priced, true);
            }
        }

        $coupon = Str::upper(trim((string) session(self::SESSION_COUPON_KEY, '')));
        if ($coupon !== '') {
            $items = $cart->items()->get()->map(fn (ShoppingCartItem $item): array => $this->cartItemArray($item))->filter()->values();
            $merchandiseTotal = (float) $items->sum(fn (array $item) => ($item['line_subtotal'] ?? 0) + ($item['customization_total'] ?? 0));
            $quantity = (int) $items->sum('quantity');
            $validation = $this->coupons->validateForCart($coupon, $merchandiseTotal, $quantity, $this->currentUser());
            if ($validation['valid'] ?? false) {
                $cart->coupon_code = $coupon;
                $cart->coupon_id = $validation['coupon_id'] ?? null;
                $cart->last_activity_at = now();
                $cart->save();
            }
        }

        session()->forget([self::SESSION_ITEMS_KEY, self::SESSION_COUPON_KEY]);
    }

    private function hasLegacySessionCart(): bool
    {
        return count((array) session(self::SESSION_ITEMS_KEY, [])) > 0 || filled(session(self::SESSION_COUPON_KEY));
    }

    private function sessionId(): string
    {
        return (string) (session()->getId() ?: '');
    }

    private function sessionItems(): array
    {
        return collect(session(self::SESSION_ITEMS_KEY, []))
            ->map(fn (array $item): array => $this->repriceItem($item))
            ->filter()
            ->values()
            ->all();
    }

    private function storeSessionItem(array $item, bool $merge): array
    {
        $items = $this->sessionItems();
        $existingIndex = collect($items)->search(fn (array $cartItem): bool => $cartItem['key'] === $item['key']);

        if ($existingIndex !== false && $merge) {
            $product = $this->products->findBySlug((string) $item['product_slug']);
            $items[$existingIndex]['quantity'] = $product
                ? $this->sanitizeQuantity((int) $items[$existingIndex]['quantity'] + (int) $item['quantity'], $product)
                : (int) $items[$existingIndex]['quantity'] + (int) $item['quantity'];
            $items[$existingIndex]['updated_at'] = now()->toIso8601String();
        } else {
            $items[] = $item;
        }

        session()->put(self::SESSION_ITEMS_KEY, array_values($items));

        return $this->summary();
    }

    private function updateSessionItem(string $key, int $quantity): array
    {
        $items = collect($this->sessionItems())
            ->map(function (array $item) use ($key, $quantity): array {
                if (hash_equals($item['key'], $key)) {
                    $product = $this->products->findBySlug((string) $item['product_slug']);
                    $quantity = $product ? $this->sanitizeQuantity($quantity, $product) : max(1, $quantity);
                    $item['quantity'] = $quantity;
                    if ($product) {
                        $item['customization'] = $this->applyCartQuantityToCustomization((array) ($item['customization'] ?? []), $quantity);
                    }
                    $item['updated_at'] = now()->toIso8601String();
                }

                return $item;
            })
            ->values()
            ->all();

        session()->put(self::SESSION_ITEMS_KEY, $items);

        return $this->summary();
    }

    private function removeSessionItem(string $key): array
    {
        $items = collect($this->sessionItems())
            ->reject(fn (array $item): bool => hash_equals($item['key'], $key))
            ->values()
            ->all();

        session()->put(self::SESSION_ITEMS_KEY, $items);

        return $this->summary();
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
        $unitPrice = $this->unitPriceForQuantity($product, $quantity, $customization);
        $shippingCharge = $this->productShippingCharge($product, $customization, $quantity);
        $customization = $this->withFulfillmentPricing($customization, $shippingCharge, $quantity);
        $customizationUnitPrice = $this->customizationUnitPrice($product, $customization, $quantity);
        $productShippingTotal = round((float) ($shippingCharge['total'] ?? 0), 2);
        $shippingUnitPrice = $quantity > 0 ? round($productShippingTotal / $quantity, 4) : 0.0;

        return array_merge($item, [
            'key' => $item['key'] ?? $this->makeItemKey($product['slug'], $customization),
            'product' => Arr::only($product, ['id', 'slug', 'title', 'short_title', 'summary', 'sku', 'category', 'sport', 'image', 'alt', 'url', 'base_price', 'price']),
            'quantity' => $quantity,
            'quantity_min' => $this->minimumQuantityForProduct($product),
            'quantity_max' => $this->maximumQuantityForProduct($product),
            'customization' => $customization,
            'unit_price' => $unitPrice,
            'customization_unit_price' => $customizationUnitPrice,
            'shipping_unit_price' => $shippingUnitPrice,
            'line_subtotal' => round($unitPrice * $quantity, 2),
            'customization_total' => round($customizationUnitPrice * $quantity, 2),
            'line_total' => round((($unitPrice + $customizationUnitPrice) * $quantity) + $productShippingTotal, 2),
            'product_shipping_total' => $productShippingTotal,
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
        $rawConfiguration = $payload['configuration_json'] ?? ($payload['configuration'] ?? []);
        $rawConfiguration = $this->recoverLegacyConfiguration(
            $rawConfiguration,
            $payload,
            $product,
            $fallbackQuantity
        );
        $configuration = $this->normalizeConfiguration(
            $rawConfiguration,
            $product,
            $fallbackQuantity
        );
        $selectedSpeed = collect($product['production_speeds'] ?? [])->firstWhere('id', $configuration['production_speed'] ?? null);
        $selectedShipping = collect($product['shipping_methods'] ?? [])->firstWhere('id', $configuration['shipping_method'] ?? null);
        $secureDeliveryLabels = collect([$selectedSpeed['label'] ?? null, $selectedShipping['label'] ?? null])->filter()->implode(' / ');
        $fulfillment = [
            'production' => $selectedSpeed ? [
                'code' => (string) ($selectedSpeed['id'] ?? ''),
                'label' => (string) ($selectedSpeed['label'] ?? 'Standard production'),
                'description' => (string) ($selectedSpeed['description'] ?? ''),
                'price_delta' => round((float) ($selectedSpeed['price_delta'] ?? 0), 2),
                'minimum_days' => max(0, (int) ($selectedSpeed['minimum_days'] ?? 0)),
                'maximum_days' => max(
                    max(0, (int) ($selectedSpeed['minimum_days'] ?? 0)),
                    (int) ($selectedSpeed['maximum_days'] ?? $selectedSpeed['minimum_days'] ?? 0)
                ),
            ] : null,
            'shipping' => $selectedShipping ? [
                'code' => (string) ($selectedShipping['id'] ?? ''),
                'label' => (string) ($selectedShipping['label'] ?? 'Standard shipping'),
                'description' => (string) ($selectedShipping['description'] ?? ''),
                'charge_type' => (string) ($selectedShipping['charge_type'] ?? 'per_unit'),
                'minimum_days' => max(0, (int) ($selectedShipping['minimum_days'] ?? 0)),
                'maximum_days' => max(
                    max(0, (int) ($selectedShipping['minimum_days'] ?? 0)),
                    (int) ($selectedShipping['maximum_days'] ?? $selectedShipping['minimum_days'] ?? 0)
                ),
                'quote_based' => (bool) ($selectedShipping['is_quote_based'] ?? false),
            ] : null,
        ];

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
            'fulfillment' => $fulfillment,
            'artwork_files' => $artworkFiles->all(),
            // Legacy first-file fields remain for existing order/cart views.
            'artwork_path' => $firstArtwork['path'] ?? null,
            'artwork_original_name' => $firstArtwork['original_name'] ?? null,
        ];
    }

    /**
     * Older cached storefront bundles submitted the human-readable summaries but
     * left configuration_json empty. Reconstruct the editable values from those
     * summaries so existing cart lines can still be edited after deployment.
     */
    private function recoverLegacyConfiguration(
        array|string $raw,
        array $payload,
        array $product,
        int $fallbackQuantity = 1
    ): array {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        $raw = is_array($raw) ? $raw : [];

        if (empty($raw['quantities']) && ! empty($product['size_groups'])) {
            $quantities = $this->legacyQuantitiesFromSummary(
                (string) ($payload['size_summary'] ?? ''),
                (array) ($product['size_groups'] ?? [])
            );

            if ($quantities !== []) {
                $raw['quantities'] = $quantities;
            }
        }

        $selectionSummary = trim((string) ($payload['design_option'] ?? ''));
        if ($selectionSummary === '' || strcasecmp($selectionSummary, 'Configured product') === 0) {
            $selectionSummary = trim((string) ($payload['notes'] ?? ''));
        }

        if ($selectionSummary !== '') {
            $raw = $this->mergeLegacyOptionSummary(
                $raw,
                $selectionSummary,
                (array) ($product['option_groups'] ?? [])
            );
        }

        $deliverySummary = trim((string) ($payload['delivery_preference'] ?? ''));
        if ($deliverySummary !== '') {
            if (blank($raw['production_speed'] ?? null)) {
                $speed = collect($product['production_speeds'] ?? [])->first(function (array $candidate) use ($deliverySummary): bool {
                    return $this->summaryContainsLabel($deliverySummary, (string) ($candidate['label'] ?? ''));
                });

                if (filled($speed['id'] ?? null)) {
                    $raw['production_speed'] = (string) $speed['id'];
                }
            }

            if (blank($raw['shipping_method'] ?? null)) {
                $shipping = collect($product['shipping_methods'] ?? [])->first(function (array $candidate) use ($deliverySummary): bool {
                    return $this->summaryContainsLabel($deliverySummary, (string) ($candidate['label'] ?? ''));
                });

                if (filled($shipping['id'] ?? null)) {
                    $raw['shipping_method'] = (string) $shipping['id'];
                }
            }
        }

        if (empty($product['size_groups']) && ! isset($raw['order_quantity'])) {
            $raw['order_quantity'] = max(1, $fallbackQuantity);
        }

        return $raw;
    }

    private function legacyQuantitiesFromSummary(string $summary, array $sizeGroups): array
    {
        $summary = trim($summary);
        if ($summary === '' || strcasecmp($summary, 'Sizes selected in configuration') === 0) {
            return [];
        }

        $groups = collect($sizeGroups)->map(function (array $group): array {
            $sizes = collect($group['sizes'] ?? [])->map(function (array $size): array {
                return [
                    'code' => (string) ($size['code'] ?? ''),
                    'label' => (string) ($size['label'] ?? ($size['code'] ?? '')),
                ];
            })->filter(fn (array $size): bool => $size['code'] !== '')->values()->all();

            return [
                'id' => (string) ($group['id'] ?? ''),
                'label' => (string) ($group['label'] ?? ($group['id'] ?? '')),
                'sizes' => $sizes,
            ];
        })->filter(fn (array $group): bool => $group['id'] !== '')->values();

        if ($groups->isEmpty()) {
            return [];
        }

        $quantities = [];
        $segments = preg_split('/\s*;\s*/u', $summary, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($segments as $segment) {
            $groupLabel = '';
            $sizeText = trim($segment);

            if (str_contains($segment, ':')) {
                [$groupLabel, $sizeText] = array_pad(explode(':', $segment, 2), 2, '');
                $groupLabel = trim($groupLabel);
                $sizeText = trim($sizeText);
            }

            $group = $groups->first(function (array $candidate) use ($groupLabel): bool {
                if ($groupLabel === '') {
                    return false;
                }

                $needle = $this->normalizeSummaryToken($groupLabel);

                return $needle !== '' && in_array($needle, [
                    $this->normalizeSummaryToken($candidate['id']),
                    $this->normalizeSummaryToken($candidate['label']),
                ], true);
            });

            if (! $group && $groups->count() === 1) {
                $group = $groups->first();
            }

            if (! is_array($group)) {
                continue;
            }

            foreach ((array) ($group['sizes'] ?? []) as $size) {
                $aliases = array_values(array_unique(array_filter([
                    (string) ($size['label'] ?? ''),
                    (string) ($size['code'] ?? ''),
                ])));

                foreach ($aliases as $alias) {
                    $pattern = '/(?:^|[,\s])'.preg_quote($alias, '/').'\s*(?:×|x|\*)\s*(\d+)(?=$|[,\s])/iu';
                    if (! preg_match($pattern, $sizeText, $matches)) {
                        continue;
                    }

                    $quantity = max(0, min(9999, (int) ($matches[1] ?? 0)));
                    if ($quantity > 0) {
                        $quantities[$group['id'].':'.$size['code']] = $quantity;
                    }
                    break;
                }
            }
        }

        return $quantities;
    }

    private function mergeLegacyOptionSummary(array $raw, string $summary, array $optionGroups): array
    {
        $segments = preg_split('/\s*;\s*/u', $summary, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $groups = collect($optionGroups)->filter(
            fn (array $group): bool => (string) ($group['display_mode'] ?? 'customer') === 'customer'
        );

        foreach ($segments as $segment) {
            if (! str_contains($segment, ':')) {
                continue;
            }

            [$label, $valueText] = array_pad(explode(':', $segment, 2), 2, '');
            $label = $this->normalizeSummaryToken($label);
            $valueText = trim($valueText);

            if ($label === '' || $valueText === '') {
                continue;
            }

            $group = $groups->first(function (array $candidate) use ($label): bool {
                return in_array($label, [
                    $this->normalizeSummaryToken((string) ($candidate['id'] ?? '')),
                    $this->normalizeSummaryToken((string) ($candidate['label'] ?? '')),
                ], true);
            });

            if (! is_array($group) || blank($group['id'] ?? null)) {
                continue;
            }

            $groupId = (string) $group['id'];
            $type = (string) ($group['type'] ?? 'select');
            $values = collect($group['values'] ?? []);

            if ($type === 'checkbox' && empty(($raw['multi_selections'] ?? [])[$groupId])) {
                $requestedLabels = collect(preg_split('/\s*,\s*/u', $valueText, -1, PREG_SPLIT_NO_EMPTY) ?: [])
                    ->map(fn (string $value): string => $this->normalizeSummaryToken($value))
                    ->filter();

                $selected = $values->filter(function (array $value) use ($requestedLabels): bool {
                    $aliases = collect([(string) ($value['id'] ?? ''), (string) ($value['label'] ?? '')])
                        ->map(fn (string $alias): string => $this->normalizeSummaryToken($alias));

                    return $aliases->intersect($requestedLabels)->isNotEmpty();
                })->pluck('id')->map(fn ($id): string => (string) $id)->values()->all();

                if ($selected !== []) {
                    $raw['multi_selections'][$groupId] = $selected;
                }
                continue;
            }

            if (in_array($type, ['image', 'swatch', 'buttons', 'select'], true)
                && blank(($raw['selections'] ?? [])[$groupId])) {
                $requested = $this->normalizeSummaryToken($valueText);
                $selected = $values->first(function (array $value) use ($requested): bool {
                    return in_array($requested, [
                        $this->normalizeSummaryToken((string) ($value['id'] ?? '')),
                        $this->normalizeSummaryToken((string) ($value['label'] ?? '')),
                    ], true);
                });

                if (filled($selected['id'] ?? null)) {
                    $raw['selections'][$groupId] = (string) $selected['id'];
                }
                continue;
            }

            if (! in_array($type, ['image', 'swatch', 'buttons', 'select', 'checkbox', 'file'], true)
                && ! array_key_exists($groupId, (array) ($raw['inputs'] ?? []))) {
                $raw['inputs'][$groupId] = $valueText;
            }
        }

        return $raw;
    }

    private function summaryContainsLabel(string $summary, string $label): bool
    {
        $summary = $this->normalizeSummaryToken($summary);
        $label = $this->normalizeSummaryToken($label);

        return $label !== '' && str_contains(' '.$summary.' ', ' '.$label.' ');
    }

    private function normalizeSummaryToken(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower(
            preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? $value
        )));
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
        $rosterAvailable = ProductRoster::supports($product['product_profile'] ?? 'standard') && (bool) ($rosterSettings['enabled'] ?? false);
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

            abort_if(count($desiredRows) > 250, 422, 'Per-item personalization is limited to 250 pieces per configured cart line.');

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
        if (ProductRoster::supports($product['product_profile'] ?? 'standard') && ($rosterSettings['enabled'] ?? false)) {
            if (! ($rosterSettings['optional'] ?? true)) {
                abort_unless((bool) ($configuration['roster_enabled'] ?? false), 422, 'Roster details are required for this product.');
            }

            if ($configuration['roster_enabled'] ?? false) {
                $requiredFields = collect($rosterSettings['fields'] ?? [])->filter(fn ($field) => ($field['enabled'] ?? true) && ($field['required'] ?? false));
                foreach (($configuration['roster'] ?? []) as $index => $row) {
                    foreach ($requiredFields as $field) {
                        abort_unless(filled($row['values'][$field['key']] ?? null), 422, 'Complete '.$field['label'].' for item '.($index + 1).'.');
                    }
                }
            }
        }
    }

    private function sanitizeQuantity(int $quantity, array $product): int
    {
        $minimum = $this->minimumQuantityForProduct($product);
        $maximum = $this->maximumQuantityForProduct($product);

        abort_if($maximum < $minimum, 422, 'This product is currently unavailable in the required minimum quantity.');

        return min(max($quantity, $minimum), $maximum);
    }

    private function minimumQuantityForProduct(array $product): int
    {
        return max(1, (int) ($product['minimum_quantity'] ?? 1));
    }

    private function maximumQuantityForProduct(array $product): int
    {
        $minimum = $this->minimumQuantityForProduct($product);
        $maximum = min(999, max($minimum, (int) ($product['maximum_quantity'] ?? 999)));

        if (($product['track_inventory'] ?? false) && ! ($product['allow_backorder'] ?? false)) {
            $maximum = min($maximum, max(0, (int) ($product['stock_quantity'] ?? 0)));
        }

        return $maximum;
    }

    private function applyCartQuantityToCustomization(array $customization, int $quantity): array
    {
        $configuration = (array) ($customization['configuration'] ?? []);
        $quantities = collect((array) ($configuration['quantities'] ?? []))
            ->map(fn ($value): int => max(0, (int) $value))
            ->filter(fn (int $value): bool => $value > 0);

        if ($quantities->isEmpty()) {
            return $customization;
        }

        $total = max(1, (int) $quantities->sum());

        if ($quantities->count() === 1) {
            $configuration['quantities'] = [$quantities->keys()->first() => $quantity];
            $customization['configuration'] = $configuration;

            return $customization;
        }

        $distributed = [];
        $fractions = [];
        $assigned = 0;

        foreach ($quantities as $key => $value) {
            $raw = ($value / $total) * $quantity;
            $base = (int) floor($raw);
            $distributed[(string) $key] = $base;
            $fractions[(string) $key] = $raw - $base;
            $assigned += $base;
        }

        $remaining = max(0, $quantity - $assigned);
        arsort($fractions);

        foreach (array_keys($fractions) as $key) {
            if ($remaining <= 0) {
                break;
            }
            $distributed[$key] = ($distributed[$key] ?? 0) + 1;
            $remaining--;
        }

        $configuration['quantities'] = collect($distributed)
            ->filter(fn (int $value): bool => $value > 0)
            ->all();
        $customization['configuration'] = $configuration;

        return $customization;
    }

    private function makeItemKey(string $slug, array $customization): string
    {
        return sha1($slug.'|'.json_encode($customization, JSON_THROW_ON_ERROR));
    }

    private function unitPriceForQuantity(array $product, int $quantity, ?array $customization = null): float
    {
        $tiers = $this->selectedFabricPriceTiers($product, $customization ?? []) ?: ($product['price_tiers'] ?? []);
        $tier = collect($tiers)->first(function (array $tier) use ($quantity): bool {
            return $quantity >= (int) ($tier['min'] ?? 1)
                && (($tier['max'] ?? null) === null || $quantity <= (int) $tier['max']);
        });

        return round((float) ($tier['unit'] ?? $product['base_price'] ?? 0), 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function selectedFabricPriceTiers(array $product, array $customization): array
    {
        $table = $this->selectedFabricPriceTable($product, $customization);
        $tiers = data_get($table, 'price_tiers', []);

        return is_array($tiers) ? $tiers : [];
    }

    private function selectedFabricPriceTable(array $product, array $customization): ?array
    {
        $configuration = $customization['configuration'] ?? [];
        $groups = collect($product['option_groups'] ?? [])->keyBy('id');

        foreach ((array) ($configuration['selections'] ?? []) as $groupId => $valueId) {
            $value = collect($groups->get($groupId)['values'] ?? [])->firstWhere('id', $valueId);
            $table = data_get($value, 'fabric_price_table');
            if (is_array($table) && (! empty($table['rows']) || ! empty($table['price_tiers']))) {
                return $table;
            }
        }

        foreach ((array) ($configuration['multi_selections'] ?? []) as $groupId => $valueIds) {
            $values = collect($groups->get($groupId)['values'] ?? [])->keyBy('id');
            foreach ((array) $valueIds as $valueId) {
                $table = data_get($values->get($valueId), 'fabric_price_table');
                if (is_array($table) && (! empty($table['rows']) || ! empty($table['price_tiers']))) {
                    return $table;
                }
            }
        }

        return null;
    }

    private function shippingPriceTable(array $product, array $customization): array
    {
        $fabricTable = $this->selectedFabricPriceTable($product, $customization);
        if (is_array($fabricTable)) {
            return [
                'headers' => (array) ($fabricTable['headers'] ?? []),
                'rows' => (array) ($fabricTable['rows'] ?? []),
                'price_tiers' => (array) ($fabricTable['price_tiers'] ?? []),
            ];
        }

        $table = (array) ($product['price_table'] ?? []);

        return [
            'headers' => (array) ($table['headers'] ?? []),
            'rows' => (array) ($table['rows'] ?? []),
            'price_tiers' => (array) ($table['price_tiers'] ?? ($product['price_tiers'] ?? [])),
        ];
    }

    private function shippingPerUnitFromPriceTable(array $product, array $customization, array $shipping, int $quantity): ?float
    {
        $table = $this->shippingPriceTable($product, $customization);

        return PriceTableShipping::perUnitRate(
            (array) ($table['headers'] ?? []),
            (array) ($table['rows'] ?? []),
            (array) ($table['price_tiers'] ?? []),
            $quantity,
            (string) ($shipping['label'] ?? ''),
            (string) ($shipping['id'] ?? '')
        );
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

        if ($quantity > 0) {
            // Sizes determine only the total quantity. The price tier chosen for that
            // total quantity determines the base unit price; sizes never add a price.
            $perUnit += $fixedOrder / $quantity;
        }

        return round($perUnit, 2);
    }

    /**
     * Calculate the product-level shipping selected in the configurator.
     *
     * The amount is returned separately from customization so the storefront,
     * checkout, order records, and invoices can label it as shipping.
     */
    private function productShippingCharge(array $product, array $customization, int $quantity): array
    {
        $configuration = (array) ($customization['configuration'] ?? []);
        $shipping = collect($product['shipping_methods'] ?? [])->firstWhere('id', $configuration['shipping_method'] ?? null);

        if (! is_array($shipping)) {
            return [
                'per_unit' => 0.0,
                'fixed_order' => 0.0,
                'total' => 0.0,
                'price_status' => 'not_applicable',
            ];
        }

        $perUnit = 0.0;
        $fixedOrder = 0.0;
        $priceStatus = 'priced';
        $tableRate = $this->shippingPerUnitFromPriceTable($product, $customization, $shipping, $quantity);

        if ($tableRate !== null) {
            $perUnit = max(0, $tableRate);
        } elseif (($shipping['price_source'] ?? null) === 'price_table' || ($shipping['requires_price_table'] ?? false) || ($shipping['charge_type'] ?? null) === 'price_table') {
            $priceStatus = 'contact_us';
        } elseif (($shipping['charge_type'] ?? 'per_unit') === 'master_method') {
            $base = max(0, (float) ($shipping['base_price'] ?? $shipping['price_delta'] ?? 0));
            $perItem = max(0, (float) ($shipping['per_item_price'] ?? 0));
            $fixedOrder = max(0, $base - $perItem);
            $perUnit = $perItem;
        } else {
            $amount = max(0, (float) ($shipping['price_delta'] ?? 0));
            if (($shipping['charge_type'] ?? 'per_unit') === 'fixed_order') {
                $fixedOrder = $amount;
            } elseif (! in_array(($shipping['charge_type'] ?? 'per_unit'), ['included', 'price_table'], true)) {
                $perUnit = $amount;
            }
        }

        return [
            'per_unit' => round($perUnit, 4),
            'fixed_order' => round($fixedOrder, 2),
            'total' => round(($perUnit * max(1, $quantity)) + $fixedOrder, 2),
            'price_status' => $priceStatus,
        ];
    }

    private function withFulfillmentPricing(array $customization, array $shippingCharge, int $quantity): array
    {
        $fulfillment = (array) ($customization['fulfillment'] ?? []);
        $production = is_array($fulfillment['production'] ?? null) ? $fulfillment['production'] : null;
        $shipping = is_array($fulfillment['shipping'] ?? null) ? $fulfillment['shipping'] : null;

        if ($production !== null) {
            $production['amount'] = round(max(0, (float) ($production['price_delta'] ?? 0)) * max(1, $quantity), 2);
            $production['display_amount'] = $production['amount'] > 0
                ? '$'.number_format((float) $production['amount'], 2)
                : 'Included';
        }

        if ($shipping !== null) {
            $shipping['amount'] = round((float) ($shippingCharge['total'] ?? 0), 2);
            $shipping['display_amount'] = ($shippingCharge['price_status'] ?? 'priced') === 'contact_us'
                ? 'Contact us for price'
                : '$'.number_format((float) $shipping['amount'], 2);
            $shipping['price_status'] = (string) ($shippingCharge['price_status'] ?? 'priced');
        }

        $productionMinimum = max(0, (int) ($production['minimum_days'] ?? 0));
        $productionMaximum = max($productionMinimum, (int) ($production['maximum_days'] ?? $productionMinimum));
        $shippingMinimum = max(0, (int) ($shipping['minimum_days'] ?? 0));
        $shippingMaximum = max($shippingMinimum, (int) ($shipping['maximum_days'] ?? $shippingMinimum));

        $customization['fulfillment'] = [
            'production' => $production,
            'shipping' => $shipping,
            'estimated_minimum_days' => $productionMinimum + $shippingMinimum,
            'estimated_maximum_days' => $productionMaximum + $shippingMaximum,
        ];

        return $customization;
    }

    private function calculateDiscount(float $merchandiseTotal, ?string $couponCode): float
    {
        $validation = $this->coupons->validateForCart($couponCode, $merchandiseTotal, 1, $this->currentUser());

        return ($validation['valid'] ?? false) ? (float) $validation['discount'] : 0.00;
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

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
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
