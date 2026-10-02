<?php

namespace App\Services\Rewards;

use App\Models\CustomerReferral;
use App\Models\CustomerRewardProfile;
use App\Models\Order;
use App\Models\RewardProgramSetting;
use App\Models\RewardTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class RewardService
{
    public const SESSION_USE_REWARDS = 'nextplay_rewards.use_balance';

    public function settings(): RewardProgramSetting
    {
        return RewardProgramSetting::current();
    }

    public function profile(User $user): CustomerRewardProfile
    {
        return $user->rewardProfile()->firstOrCreate([], [
            'progress_amount' => 0,
        ]);
    }

    public function availableBalance(User $user): float
    {
        return round((float) RewardTransaction::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['posted', 'reserved', 'redeemed'])
            ->sum('amount'), 2);
    }

    /** @return array<string,mixed> */
    public function pageData(User $user): array
    {
        $settings = $this->settings();
        $profile = $this->profile($user);
        $target = max(0.01, (float) ($profile->progress_target_override ?? $settings->default_progress_target));
        $rewardAmount = max(0, (float) ($profile->reward_amount_override ?? $settings->default_reward_amount));
        $spent = max(0, (float) $profile->progress_amount);
        $remaining = max(0, $target - $spent);
        $progressPercent = (int) round(min(100, max(0, ($spent / $target) * 100)));

        $activity = RewardTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (RewardTransaction $transaction): array {
                $isReleasedRedemption = $transaction->status === 'void'
                    && $transaction->type === 'order_redemption'
                    && (float) $transaction->amount < 0;

                return [
                    'date' => $transaction->created_at?->format('d M Y') ?? '',
                    'activity' => $isReleasedRedemption
                        ? 'Reward restored after order cancellation'
                        : ($transaction->description ?: str($transaction->type)->headline()->toString()),
                    'progress' => $isReleasedRedemption ? abs((float) $transaction->amount) : (float) $transaction->amount,
                    'status' => match ($transaction->status) {
                        'reserved' => 'Reserved for order',
                        'redeemed' => 'Used',
                        'void' => 'Released',
                        default => (float) $transaction->amount >= 0 ? 'Available' : 'Applied',
                    },
                ];
            })
            ->all();

        return [
            'is_example' => false,
            'spent' => $spent,
            'target' => $target,
            'reward_value' => $rewardAmount,
            'remaining' => $remaining,
            'progress_percent' => $progressPercent,
            'available_rewards' => max(0, $this->availableBalance($user)),
            'activity' => $activity,
            'programme_active' => (bool) $settings->is_active,
        ];
    }

    /** @return array<string,mixed> */
    public function cartAdjustment(float $eligibleAmount, ?User $user): array
    {
        if (! $user instanceof User || ! $user->isCustomer()) {
            return ['available' => 0.0, 'requested' => false, 'applied' => false, 'amount' => 0.0];
        }

        $settings = $this->settings();
        $available = $settings->is_active ? max(0, $this->availableBalance($user)) : 0.0;
        $requested = (bool) session(self::SESSION_USE_REWARDS, false);
        $amount = $requested ? min($available, max(0, round($eligibleAmount, 2))) : 0.0;

        return [
            'available' => round($available, 2),
            'requested' => $requested,
            'applied' => $amount > 0,
            'amount' => round($amount, 2),
        ];
    }

    public function requestCartRedemption(User $user): void
    {
        if (! $this->settings()->is_active) {
            throw ValidationException::withMessages(['rewards' => 'The rewards programme is currently unavailable.']);
        }

        if ($this->availableBalance($user) <= 0) {
            throw ValidationException::withMessages(['rewards' => 'You do not currently have an available reward balance.']);
        }

        session()->put(self::SESSION_USE_REWARDS, true);
    }

    public function clearCartRedemption(): void
    {
        session()->forget(self::SESSION_USE_REWARDS);
    }

    public function reserveForOrder(Order $order, User $user, float $amount): ?RewardTransaction
    {
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return null;
        }

        $sourceKey = 'order:'.$order->id.':reward-redemption';
        $existing = RewardTransaction::query()->where('source_key', $sourceKey)->first();
        if ($existing instanceof RewardTransaction) {
            return $existing;
        }

        User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
        $available = $this->availableBalance($user);
        if ($available + 0.00001 < $amount) {
            throw ValidationException::withMessages([
                'rewards' => 'Your available reward balance changed before the order was placed. Review the updated total and try again.',
            ]);
        }

        return RewardTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'order_redemption',
            'status' => 'reserved',
            'amount' => -$amount,
            'source_key' => $sourceKey,
            'description' => 'Reward applied to order '.$order->order_number,
        ]);
    }

    public function redeemOrderReservation(Order $order): void
    {
        RewardTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', 'order_redemption')
            ->where('status', 'reserved')
            ->update(['status' => 'redeemed', 'updated_at' => now()]);
    }

    public function releaseOrderReservation(Order $order): void
    {
        RewardTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', 'order_redemption')
            ->whereIn('status', ['reserved', 'redeemed'])
            ->update(['status' => 'void', 'updated_at' => now()]);
    }

    public function grant(User $user, float $amount, User $admin, string $reason): RewardTransaction
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Reward amount must be greater than zero.']);
        }

        return RewardTransaction::create([
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'type' => 'admin_grant',
            'status' => 'posted',
            'amount' => $amount,
            'description' => trim($reason) !== '' ? trim($reason) : 'Reward granted by admin',
            'metadata' => ['admin_name' => $admin->name],
        ]);
    }

    public function updateProfile(User $user, array $data, User $admin): CustomerRewardProfile
    {
        $profile = $this->profile($user);
        $profile->update([
            'progress_amount' => round((float) $data['progress_amount'], 2),
            'progress_target_override' => filled($data['progress_target_override'] ?? null) ? round((float) $data['progress_target_override'], 2) : null,
            'reward_amount_override' => filled($data['reward_amount_override'] ?? null) ? round((float) $data['reward_amount_override'], 2) : null,
            'updated_by' => $admin->id,
        ]);

        return $profile->fresh();
    }

    public function recordReferralOrder(Order $order, ?User $user, array $offer): CustomerReferral
    {
        if (! $user instanceof User || (int) $order->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'referral' => 'The referral customer could not be verified for this order.',
            ]);
        }

        $existingForOrder = CustomerReferral::query()->where('order_id', $order->id)->first();
        if ($existingForOrder instanceof CustomerReferral) {
            return $existingForOrder;
        }

        $referrerId = (int) ($offer['referrer_id'] ?? 0);
        $referrer = User::query()
            ->whereKey($referrerId)
            ->where('role', 'customer')
            ->where('is_active', true)
            ->first();

        if (
            ! $referrer instanceof User
            || $referrerId === (int) $user->id
            || (int) ($user->getAttribute('referred_by_user_id') ?? 0) !== $referrerId
        ) {
            throw ValidationException::withMessages([
                'referral' => 'The referral attribution is no longer valid for this customer.',
            ]);
        }

        if (! hash_equals(
            Str::lower(trim((string) $user->email)),
            Str::lower(trim((string) $order->customer_email))
        )) {
            throw ValidationException::withMessages([
                'email' => 'The referral order email must match the new customer account.',
            ]);
        }

        if (CustomerReferral::query()->where('referred_user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'referral' => 'This customer has already used a referral offer.',
            ]);
        }

        return CustomerReferral::query()->create([
            'order_id' => $order->id,
            'referrer_id' => $referrerId,
            'referred_user_id' => $user->id,
            'status' => 'pending',
            'friend_reward_amount' => round((float) ($offer['amount'] ?? 0), 2),
            'referrer_reward_amount' => round((float) ($offer['referrer_reward_amount'] ?? 0), 2),
            'minimum_order' => round((float) ($offer['minimum_order'] ?? 0), 2),
        ]);
    }

    public function cancelReferralForOrder(Order $order): void
    {
        CustomerReferral::query()
            ->where('order_id', $order->id)
            ->whereNull('rewarded_at')
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
    }

    public function completeReferralForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $referral = CustomerReferral::query()->where('order_id', $order->id)->lockForUpdate()->first();
            if (
                ! $referral instanceof CustomerReferral
                || $referral->rewarded_at
                || $referral->status === 'cancelled'
                || $order->payment_status !== 'paid'
                || $order->status !== 'completed'
                || (int) $order->user_id !== (int) $referral->referred_user_id
                || (int) $referral->referrer_id === (int) $referral->referred_user_id
            ) {
                return;
            }

            $referral->update([
                'status' => 'completed',
                'completed_at' => $referral->completed_at ?: now(),
            ]);

            $amount = round((float) $referral->referrer_reward_amount, 2);
            if ($amount > 0) {
                RewardTransaction::query()->firstOrCreate(
                    ['source_key' => 'referral:'.$referral->id.':referrer-reward'],
                    [
                        'user_id' => $referral->referrer_id,
                        'customer_referral_id' => $referral->id,
                        'order_id' => $order->id,
                        'type' => 'referral_reward',
                        'status' => 'posted',
                        'amount' => $amount,
                        'description' => 'Refer-a-friend reward from order '.$order->order_number,
                    ]
                );
            }

            $referral->update(['rewarded_at' => now()]);
        });
    }
}
