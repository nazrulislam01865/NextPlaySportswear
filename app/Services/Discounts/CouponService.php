<?php

namespace App\Services\Discounts;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CouponService
{
    public function validateForCart(?string $code, float $merchandiseTotal, int $quantity = 0, ?User $user = null, ?string $email = null): array
    {
        $code = $this->normalizeCode($code);

        if ($code === '') {
            return $this->invalid('Enter a promo code.');
        }

        if ($merchandiseTotal <= 0 || $quantity <= 0) {
            return $this->invalid('Add at least one product before applying a promo code.', $code);
        }

        if (! Schema::hasTable('coupons')) {
            return $this->validateLegacyCoupon($code, $merchandiseTotal);
        }

        $coupon = Coupon::query()
            ->where('code', $code)
            ->first();

        if (! $coupon instanceof Coupon) {
            return $this->invalid('This promo code does not exist. Please check the code and try again.', $code);
        }

        if (! $coupon->is_active) {
            return $this->invalid('This promo code is currently inactive.', $code, $coupon);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $this->invalid('This promo code is not active yet.', $code, $coupon);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return $this->invalid('This promo code has expired.', $code, $coupon);
        }

        if ((float) $coupon->minimum_subtotal > 0 && $merchandiseTotal < (float) $coupon->minimum_subtotal) {
            return $this->invalid(
                'This promo code requires a merchandise subtotal of at least $'.number_format((float) $coupon->minimum_subtotal, 2).'.',
                $code,
                $coupon
            );
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $this->invalid('This promo code has reached its usage limit.', $code, $coupon);
        }

        if ($coupon->usage_limit_per_customer !== null && $coupon->usage_limit_per_customer > 0) {
            $customerUsage = $this->customerUsageCount($coupon, $user, $email);
            if ($customerUsage >= $coupon->usage_limit_per_customer) {
                return $this->invalid('You have already used this promo code the maximum allowed number of times.', $code, $coupon);
            }
        }

        $discount = $this->calculateDiscount($coupon, $merchandiseTotal);

        if ($discount <= 0) {
            return $this->invalid('This promo code does not reduce the current cart total.', $code, $coupon);
        }

        return [
            'valid' => true,
            'code' => $code,
            'message' => 'Promo code '.$code.' applied successfully.',
            'discount' => round($discount, 2),
            'coupon' => $coupon,
            'coupon_id' => $coupon->id,
            'snapshot' => $this->snapshot($coupon),
        ];
    }

    public function recordRedemption(Coupon $coupon, Order $order, float $merchandiseTotal, float $discountAmount, ?ShoppingCart $cart = null, ?User $user = null): void
    {
        if ($discountAmount <= 0 || ! Schema::hasTable('coupon_redemptions')) {
            return;
        }

        DB::transaction(function () use ($coupon, $order, $merchandiseTotal, $discountAmount, $cart, $user): void {
            $redemption = CouponRedemption::query()->firstOrCreate(
                [
                    'coupon_id' => $coupon->id,
                    'order_id' => $order->id,
                ],
                [
                    'user_id' => $user?->id ?: $order->user_id,
                    'shopping_cart_id' => $cart?->id,
                    'customer_email' => Str::lower((string) $order->customer_email),
                    'coupon_code' => $coupon->code,
                    'cart_subtotal' => round($merchandiseTotal, 2),
                    'discount_amount' => round($discountAmount, 2),
                    'redeemed_at' => now(),
                ]
            );

            if ($redemption->wasRecentlyCreated) {
                $coupon->increment('used_count');
            }
        });
    }

    public function normalizeCode(?string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $code)) ?? '');
    }

    public function snapshot(Coupon $coupon): array
    {
        return Arr::only($coupon->toArray(), [
            'id',
            'name',
            'code',
            'description',
            'discount_type',
            'discount_value',
            'minimum_subtotal',
            'maximum_discount',
            'usage_limit',
            'usage_limit_per_customer',
            'starts_at',
            'expires_at',
            'is_active',
        ]);
    }

    private function calculateDiscount(Coupon $coupon, float $merchandiseTotal): float
    {
        if ($coupon->discount_type === 'percentage') {
            $discount = $merchandiseTotal * ((float) $coupon->discount_value / 100);
        } else {
            $discount = (float) $coupon->discount_value;
        }

        if ($coupon->maximum_discount !== null) {
            $discount = min($discount, (float) $coupon->maximum_discount);
        }

        return round(min($merchandiseTotal, max(0, $discount)), 2);
    }

    private function customerUsageCount(Coupon $coupon, ?User $user, ?string $email): int
    {
        if (! Schema::hasTable('coupon_redemptions')) {
            return 0;
        }

        $query = CouponRedemption::query()->where('coupon_id', $coupon->id);

        if ($user instanceof User) {
            return (int) $query->where('user_id', $user->id)->count();
        }

        $email = Str::lower(trim((string) $email));

        if ($email === '') {
            return 0;
        }

        return (int) $query->where('customer_email', $email)->count();
    }

    private function validateLegacyCoupon(string $code, float $merchandiseTotal): array
    {
        $legacy = [
            'TEAM10' => ['type' => 'percentage', 'value' => 10, 'minimum' => 100, 'max_discount' => 75],
            'NEXTPLAY25' => ['type' => 'fixed', 'value' => 25, 'minimum' => 250, 'max_discount' => 25],
        ][$code] ?? null;

        if ($legacy === null) {
            return $this->invalid('This promo code does not exist. Please check the code and try again.', $code);
        }

        if ($merchandiseTotal < $legacy['minimum']) {
            return $this->invalid('This promo code requires a merchandise subtotal of at least $'.number_format($legacy['minimum'], 2).'.', $code);
        }

        $discount = $legacy['type'] === 'percentage'
            ? min(round($merchandiseTotal * ($legacy['value'] / 100), 2), $legacy['max_discount'])
            : min($merchandiseTotal, $legacy['value']);

        return [
            'valid' => true,
            'code' => $code,
            'message' => 'Promo code '.$code.' applied successfully.',
            'discount' => round($discount, 2),
            'coupon' => null,
            'coupon_id' => null,
            'snapshot' => [
                'code' => $code,
                'discount_type' => $legacy['type'],
                'discount_value' => $legacy['value'],
                'minimum_subtotal' => $legacy['minimum'],
                'maximum_discount' => $legacy['max_discount'],
            ],
        ];
    }

    private function invalid(string $message, ?string $code = null, ?Coupon $coupon = null): array
    {
        return [
            'valid' => false,
            'code' => $code,
            'message' => $message,
            'discount' => 0.0,
            'coupon' => $coupon,
            'coupon_id' => $coupon?->id,
            'snapshot' => $coupon instanceof Coupon ? $this->snapshot($coupon) : null,
        ];
    }
}
