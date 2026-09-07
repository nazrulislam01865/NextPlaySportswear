<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider',
    'provider_event_id',
    'event_type',
    'order_payment_id',
    'payload_hash',
    'payload',
    'status',
    'received_at',
    'processed_at',
    'last_error',
])]
class PaymentWebhookEvent extends Model
{
    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
