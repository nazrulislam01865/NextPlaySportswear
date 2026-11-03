<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeRefundSync extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['livemode' => 'boolean', 'checked_at' => 'datetime'];
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(OrderRefund::class, 'order_refund_id');
    }
}
