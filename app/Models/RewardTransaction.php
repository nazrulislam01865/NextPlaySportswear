<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'order_id', 'customer_referral_id', 'admin_id', 'type', 'status', 'amount',
    'source_key', 'description', 'metadata',
])]
class RewardTransaction extends Model
{
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function referral(): BelongsTo { return $this->belongsTo(CustomerReferral::class, 'customer_referral_id'); }
    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'metadata' => 'array'];
    }
}
