<?php

namespace App\Services\Referrals;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

final class ReferralOfferService
{
    public const SESSION_KEY = 'nextplay_referral.offer';
    public const NEW_CUSTOMER_SESSION_KEY = 'nextplay_referral.new_customer_user_id';

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

    /**
     * @return array<string, mixed>|null
     */
    public function activate(string $token): ?array
    {
        $referrer = $this->resolveReferrer($token);

        if (! $referrer instanceof User) {
            return null;
        }

        $current = Auth::guard('web')->user();
        if ($current instanceof User && $current->is($referrer)) {
            return null;
        }

        $offer = [
            'referrer_id' => (int) $referrer->getKey(),
            'token_hash' => hash('sha256', $token),
            'reward_amount' => self::REWARD_AMOUNT,
            'minimum_order' => self::MINIMUM_ORDER,
            'activated_at' => now()->toIso8601String(),
        ];

        session()->put(self::SESSION_KEY, $offer);

        return $this->current($current);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function current(?User $customer = null): ?array
    {
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

        return array_merge($offer, [
            'active' => true,
            'customer_eligible' => $customerEligible,
            'customer_status' => $customer instanceof User
                ? ($customerEligible ? 'eligible' : 'ineligible')
                : 'pending',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function cartAdjustment(float $merchandiseTotal, bool $hasCoupon = false, ?User $customer = null): array
    {
        $offer = $this->current($customer);
        $linked = is_array($offer);
        $meetsMinimum = $linked && $merchandiseTotal >= self::MINIMUM_ORDER;
        $customerEligible = $linked && (bool) ($offer['customer_eligible'] ?? false);
        $pendingCustomer = $linked && ($offer['customer_status'] ?? null) === 'pending';
        $canApply = $linked && $meetsMinimum && ($customerEligible || $pendingCustomer) && ! $hasCoupon;

        return [
            'linked' => $linked,
            'applied' => $canApply,
            'amount' => $canApply ? self::REWARD_AMOUNT : 0.00,
            'reward_amount' => self::REWARD_AMOUNT,
            'minimum_order' => self::MINIMUM_ORDER,
            'meets_minimum' => $meetsMinimum,
            'customer_eligible' => $customerEligible,
            'customer_status' => $offer['customer_status'] ?? 'none',
            'blocked_by_coupon' => $linked && $hasCoupon,
        ];
    }

    public function attachNewCustomer(User $user): void
    {
        if (! is_array($this->current())) {
            return;
        }

        session()->put(self::NEW_CUSTOMER_SESSION_KEY, (int) $user->getKey());
    }

    public function clear(): void
    {
        session()->forget([self::SESSION_KEY, self::NEW_CUSTOMER_SESSION_KEY]);
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

        if ($customer->is($referrer)) {
            return false;
        }

        $registeredFromOffer = (int) session(self::NEW_CUSTOMER_SESSION_KEY, 0) === (int) $customer->getKey();

        return $registeredFromOffer && ! $customer->orders()->exists();
    }
}
