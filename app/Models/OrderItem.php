<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id', 'product_id', 'product_slug', 'product_name', 'sku', 'image_url', 'quantity',
    'fulfilled_quantity', 'cancelled_quantity', 'returned_quantity', 'unit_price',
    'customization_unit_price', 'line_total', 'customization', 'is_digital',
])]
class OrderItem extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(OrderShipmentItem::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function remainingCancellableQuantity(): int
    {
        return max(0, $this->quantity - $this->fulfilled_quantity - $this->cancelled_quantity);
    }

    public function remainingFulfillableQuantity(): int
    {
        $allocated = (int) $this->shipmentItems()->sum('quantity');

        return max(0, $this->quantity - $this->cancelled_quantity - $allocated);
    }

    public function returnableQuantity(): int
    {
        $activeStatuses = ['requested', 'under_review', 'approved', 'label_issued', 'in_transit'];

        if ($this->relationLoaded('returnItems')) {
            $reserved = (int) $this->returnItems
                ->filter(fn (OrderReturnItem $returnItem): bool => in_array(
                    $returnItem->returnRequest?->status,
                    $activeStatuses,
                    true,
                ))
                ->sum('quantity');
        } else {
            $reserved = (int) $this->returnItems()
                ->whereHas('returnRequest', fn ($query) => $query->whereIn('status', $activeStatuses))
                ->sum('quantity');
        }

        return max(0, $this->fulfilled_quantity - $this->returned_quantity - $reserved);
    }

    protected function casts(): array
    {
        return [
            'customization' => 'array',
            'is_digital' => 'boolean',
            'unit_price' => 'decimal:2',
            'customization_unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
