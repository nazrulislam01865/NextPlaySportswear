<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'order_refund_id',
    'credit_note_number',
    'amount',
    'currency',
    'reason',
    'metadata',
    'issued_at',
])]
class OrderCreditNote extends Model
{
    public function getRouteKeyName(): string
    {
        return 'credit_note_number';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(OrderRefund::class, 'order_refund_id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'issued_at' => 'datetime',
        ];
    }
}
