<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'shipment_number',
    'status',
    'carrier',
    'service',
    'tracking_number',
    'tracking_url',
    'shipping_address',
    'notes',
    'shipped_at',
    'estimated_delivery_at',
    'delivered_at',
])]
class OrderShipment extends Model
{
    public function getRouteKeyName(): string
    {
        return 'shipment_number';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderShipmentItem::class);
    }

    public function statusLabel(): string
    {
        return config(
            'commerce.shipment_statuses.'.$this->status,
            str($this->status)->headline()->toString(),
        );
    }

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'shipped_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
