<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'user_id',
    'resolved_by',
    'request_number',
    'type',
    'scope',
    'status',
    'reason_code',
    'reason',
    'requested_changes',
    'admin_note',
    'resolved_at',
])]
class OrderChangeRequest extends Model
{
    public function getRouteKeyName(): string
    {
        return 'request_number';
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

    protected function casts(): array
    {
        return [
            'requested_changes' => 'array',
            'resolved_at' => 'datetime',
        ];
    }
}
