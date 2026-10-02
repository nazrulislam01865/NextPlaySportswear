<?php

namespace App\Services\Referrals;

use App\Models\RewardProgramSetting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ReferralOfferService
{
    public const SESSION_KEY = 'nextplay_referral.offer';

    // Backward-compatible fallbacks for older snapshots and tests.
    public const REWARD_AMOUNT = 5.00;
    public const MINIMUM_ORDER = 50.00;

    public function tokenFor(User $user): string
    {
        $encrypted = Crypt::encryptString((string) $user->getKey());

        return rtrim(strtr($encrypted, '+/', '-_'), '=');
    }

    public function shareUrl(User $user): string
    {
        return route('referral.offer', ['token' => $this->tokenFor($user)]);
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $settings = RewardProgramSetting::current();

        return [
            'enabled' => (bool) $settings->referral_enabled && (bool) $settings->is_active,
            'friend_reward_amount' => round((float) $settings->referral_friend_reward_amount, 2),
            'referrer_reward_amount' => round((float) $settings->referral_referrer_reward_amount, 2),
            'minimum_order' => round((float) $settings->referral_minimum_order, 2),
        ];
    }

    /** @return array<string, mixed>|null */
    public function activate(string $token): ?array
    {
        $program = $this->settings();
        if (! $program['enabled']) {
            $this->clear();
            return null;
        }

        $referrer = $this->resolveReferrer($token);
        if (! $referrer instanceof User) {
            $this->clear();
            return null;
        }

        $current = Auth::guard('web')->user();
        if ($current instanceof User && ! $this->customerIsEligible($current, $referrer)) {
            $this->clear();
            return null;
        }

        $offer = [
            'referrer_id' => (int) $referrer->getKey(),
            'token_hash' => hash('sha256', $token),
            'reward_amount' => $program['friend_reward_amount'],
            'referrer_reward_amount' => $program['referrer_reward_amount'],
            'minimum_order' => $program['minimum_order'],
            'activated_at' => now()->toIso8601String(),
        ];

        session()->put(self::SESSION_KEY, $offer);

        return $this->current($current);
    }

    /** @return array<string, mixed>|null */
    public function current(?User $customer = null): ?array
    {
        $program = $this->settings();
        if (! $program['enabled']) {
            $this->clear();
            return null;
        }

        $offer = session(self::SESSION_KEY);
        if (! is_array($offer) || empty($offer['referrer_id'])) {
            return null;
        }

        $referrer = User::query()
            ->whereKey((int) $offer['referrer_id'])
            ->where('role', 'customer')
            ->where('is_active', true)
            ->first();

        if (! $referrer instanceof User) {
            $this->clear();
            return null;
        }

        $customer ??= Auth::guard('web')->user();
        $customerEligible = $this->customerIsEligible($customer, $referrer);

        // An authenticated account must already be the new account that claimed
        // this referral. Existing customers and accounts claimed by another
        // referrer must never carry a referral offer into cart or checkout.
        if ($customer instanceof User && ! $customerEligible) {
            $this->clear();
            return null;
        }

        return array_merge($offer, [
            'reward_amount' => $program['friend_reward_amount'],
            'referrer_reward_amount' => $program['referrer_reward_amount'],
            'minimum_order' => $program['minimum_order'],
            'active' => true,
            'customer_eligible' => $customerEligible,
            'customer_status' => $customer instanceof User ? 'eligible' : 'pending',
        ]);
    }

    /** @return array<string, mixed> */
    public function cartAdjustment(float $merchandiseTotal, bool $hasCoupon = false, ?User $customer = null): array
    {
        $program = $this->settings();
        $offer = $this->current($customer);
        $linked = is_array($offer);
        $minimumOrder = (float) $program['minimum_order'];
        $friendReward = (float) $program['friend_reward_amount'];
        $meetsMinimum = $linked && $merchandiseTotal >= $minimumOrder;
        $customerEligible = $linked && (bool) ($offer['customer_eligible'] ?? false);
        $pendingCustomer = $linked && ($offer['customer_status'] ?? null) === 'pending';
        $canApply = $linked && $meetsMinimum && ($customerEligible || $pendingCustomer) && ! $hasCoupon;
        $discount = $canApply ? min(max(0, $friendReward), max(0, $merchandiseTotal)) : 0.00;

        return [
            'linked' => $linked,
            'applied' => $canApply,
            'amount' => round($discount, 2),
            'reward_amount' => $friendReward,
            'referrer_reward_amount' => (float) $program['referrer_reward_amount'],
            'minimum_order' => $minimumOrder,
            'meets_minimum' => $meetsMinimum,
            'customer_eligible' => $customerEligible,
            'customer_status' => $offer['customer_status'] ?? 'none',
            'blocked_by_coupon' => $linked && $hasCoupon,
        ];
    }

    public function attachNewCustomer(User $user): void
    {
        $offer = session(self::SESSION_KEY);
        if (! is_array($offer) || empty($offer['referrer_id']) || ! $this->settings()['enabled']) {
            return;
        }

        $referrer = User::query()
            ->whereKey((int) $offer['referrer_id'])
            ->where('role', 'customer')
            ->where('is_active', true)
            ->first();

        if (! $referrer instanceof User || $user->is($referrer) || $user->orders()->exists()) {
            $this->clear();
            return;
        }

        $existingReferrerId = (int) ($user->getAttribute('referred_by_user_id') ?? 0);
        if ($existingReferrerId > 0 && $existingReferrerId !== (int) $referrer->id) {
            $this->clear();
            return;
        }

        if ($existingReferrerId === 0) {
            $user->forceFill(['referred_by_user_id' => $referrer->id])->save();
        }

    }

    /**
     * Final server-side validation for a referral order. This method is called
     * again from the order transaction after the customer row is locked.
     *
     * @return array<string, mixed>
     */
    public function assertEligibleForCheckout(
        User $customer,
        string $checkoutEmail,
        float $merchandiseTotal,
        bool $hasCoupon = false,
    ): array {
        $offer = $this->current($customer);
        if (! is_array($offer) || ! ($offer['customer_eligible'] ?? false)) {
            throw ValidationException::withMessages([
                'referral' => 'This referral offer is only available to the new customer account that claimed the referral link.',
            ]);
        }

        if (! hash_equals(
            Str::lower(trim((string) $customer->email)),
            Str::lower(trim($checkoutEmail))
        )) {
            throw ValidationException::withMessages([
                'email' => 'Use the email address of the new account that claimed this referral offer.',
            ]);
        }

        if ($customer->orders()->exists()) {
            throw ValidationException::withMessages([
                'referral' => 'This referral offer can only be used once on the customer’s first order.',
            ]);
        }

        if ($hasCoupon) {
            throw ValidationException::withMessages([
                'referral' => 'Referral offers cannot be combined with a promo code. Remove the promo code to use this offer.',
            ]);
        }

        $minimumOrder = round((float) ($offer['minimum_order'] ?? 0), 2);
        if (round($merchandiseTotal, 2) + 0.00001 < $minimumOrder) {
            throw ValidationException::withMessages([
                'referral' => 'Your eligible items must total at least £'.number_format($minimumOrder, 2).' to use this referral offer.',
            ]);
        }

        $offer['discount_amount'] = round(min(
            max(0, (float) ($offer['reward_amount'] ?? 0)),
            max(0, $merchandiseTotal),
        ), 2);

        return $offer;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function resolveReferrer(string $token): ?User
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 4096) {
            return null;
        }

        $base64 = strtr($token, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding !== 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        try {
            $id = (int) Crypt::decryptString($base64);
        } catch (DecryptException) {
            return null;
        }

        if ($id < 1) {
            return null;
        }

        return User::query()
            ->whereKey($id)
            ->where('role', 'customer')
            ->where('is_active', true)
            ->first();
    }

    private function customerIsEligible(?User $customer, User $referrer): bool
    {
        if (! $customer instanceof User || ! $customer->isCustomer()) {
            return false;
        }

        if ($customer->is($referrer) || $customer->orders()->exists()) {
            return false;
        }

        return (int) ($customer->getAttribute('referred_by_user_id') ?? 0) === (int) $referrer->id;
    }
}
