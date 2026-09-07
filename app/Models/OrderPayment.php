<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id', 'payment_method_id', 'gateway', 'idempotency_key', 'provider',
    'provider_reference', 'provider_session_id', 'provider_payment_id', 'provider_customer_id',
    'status', 'amount', 'currency', 'failure_code', 'failure_message', 'metadata',
    'attempted_at', 'authorized_at', 'paid_at', 'failed_at', 'expires_at', 'refunded_amount',
])]
class OrderPayment extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'attempted_at' => 'datetime',
            'authorized_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
