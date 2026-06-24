<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'user_id',
    'resolved_by',
    'return_number',
    'type',
    'status',
    'reason_code',
    'reason',
    'requested_resolution',
    'exchange_notes',
    'approved_amount',
    'admin_note',
    'requested_at',
    'received_at',
    'completed_at',
    'resolved_at',
])]
class OrderReturnRequest extends Model
{
    public function getRouteKeyName(): string
    {
        return 'return_number';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderReturnAttachment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function statusLabel(): string
    {
        return config(
            'commerce.return_statuses.'.$this->status,
            str($this->status)->headline()->toString(),
        );
    }

    protected function casts(): array
    {
        return [
            'approved_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
