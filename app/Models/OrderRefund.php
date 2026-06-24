<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'order_id',
    'order_return_request_id',
    'order_payment_id',
    'refund_number',
    'amount',
    'currency',
    'method',
    'status',
    'provider_reference',
    'reason',
    'notes',
    'processed_at',
])]
class OrderRefund extends Model
{
    public function getRouteKeyName(): string
    {
        return 'refund_number';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(OrderReturnRequest::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(OrderCreditNote::class);
    }

    public function statusLabel(): string
    {
        return config(
            'commerce.refund_statuses.'.$this->status,
            str($this->status)->headline()->toString(),
        );
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }
}
