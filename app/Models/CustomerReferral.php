<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'referrer_id', 'referred_user_id', 'order_id', 'status', 'friend_reward_amount',
    'referrer_reward_amount', 'minimum_order', 'completed_at', 'rewarded_at',
])]
class CustomerReferral extends Model
{
    public function referrer(): BelongsTo { return $this->belongsTo(User::class, 'referrer_id'); }
    public function referredUser(): BelongsTo { return $this->belongsTo(User::class, 'referred_user_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }

    protected function casts(): array
    {
        return [
            'friend_reward_amount' => 'decimal:2',
            'referrer_reward_amount' => 'decimal:2',
            'minimum_order' => 'decimal:2',
            'completed_at' => 'datetime',
            'rewarded_at' => 'datetime',
        ];
    }
}
