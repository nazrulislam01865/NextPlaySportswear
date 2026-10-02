<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'is_active', 'default_progress_target', 'default_reward_amount', 'referral_enabled',
    'referral_friend_reward_amount', 'referral_referrer_reward_amount', 'referral_minimum_order', 'updated_by',
])]
class RewardProgramSetting extends Model
{
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_active' => true,
            'default_progress_target' => 100,
            'default_reward_amount' => 5,
            'referral_enabled' => true,
            'referral_friend_reward_amount' => 5,
            'referral_referrer_reward_amount' => 5,
            'referral_minimum_order' => 50,
        ]);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'referral_enabled' => 'boolean',
            'default_progress_target' => 'decimal:2',
            'default_reward_amount' => 'decimal:2',
            'referral_friend_reward_amount' => 'decimal:2',
            'referral_referrer_reward_amount' => 'decimal:2',
            'referral_minimum_order' => 'decimal:2',
        ];
    }
}
