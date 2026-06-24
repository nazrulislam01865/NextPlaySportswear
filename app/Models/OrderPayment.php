<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'provider', 'provider_reference', 'status', 'amount', 'currency', 'failure_code', 'failure_message', 'metadata', 'attempted_at', 'paid_at'])]
class OrderPayment extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function casts(): array
    {
        return ['metadata' => 'array', 'amount' => 'decimal:2', 'attempted_at' => 'datetime', 'paid_at' => 'datetime'];
    }
}
