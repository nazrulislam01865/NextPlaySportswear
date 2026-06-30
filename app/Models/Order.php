<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'order_number', 'status', 'payment_status', 'fulfillment_status', 'currency',
    'coupon_id', 'coupon_code', 'coupon_snapshot', 'customer_name', 'customer_email', 'customer_phone', 'subtotal', 'customization_total',
    'discount_total', 'shipping_total', 'rural_surcharge_total', 'product_shipping_total', 'tax_total', 'grand_total', 'total_quantity',
    'information', 'shipping_address', 'billing_address', 'shipping_method', 'payment_method',
    'customer_note', 'admin_note', 'idempotency_key', 'placed_at', 'paid_at', 'cancelled_at',
    'completed_at', 'delivered_at',
])]
class Order extends Model
{
    use HasFactory, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('occurred_at');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class)->latest();
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(OrderChangeRequest::class)->latest();
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(OrderReturnRequest::class)->latest('requested_at');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class)->latest();
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(OrderCreditNote::class)->latest('issued_at');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(OrderDownload::class)->latest();
    }

    public function statusLabel(): string
    {
        return config('commerce.order_statuses.'.$this->status, str($this->status)->headline()->toString());
    }

    public function paymentStatusLabel(): string
    {
        return config('commerce.payment_statuses.'.$this->payment_status, str($this->payment_status)->headline()->toString());
    }

    public function fulfillmentStatusLabel(): string
    {
        return config('commerce.fulfillment_statuses.'.$this->fulfillment_status, str($this->fulfillment_status)->headline()->toString());
    }

    public function canPay(): bool
    {
        return in_array($this->status, config('commerce.payable_statuses', []), true)
            && in_array($this->payment_status, ['pending', 'failed'], true)
            && $this->grand_total > 0;
    }

    public function canRequestCancellation(): bool
    {
        return in_array($this->status, config('commerce.cancellable_statuses', []), true)
            && ! $this->changeRequests()->where('type', 'cancel')->whereIn('status', ['pending', 'approved'])->exists();
    }

    public function canRequestChange(): bool
    {
        return in_array($this->status, config('commerce.changeable_statuses', []), true)
            && ! $this->changeRequests()->where('type', 'change')->whereIn('status', ['pending', 'approved'])->exists();
    }

    public function canRequestReturn(): bool
    {
        if (! in_array($this->status, ['delivered', 'completed'], true) || ! $this->delivered_at) {
            return false;
        }

        if (! $this->delivered_at->copy()->addDays((int) config('commerce.return_window_days', 30))->isFuture()) {
            return false;
        }

        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('returnItems.returnRequest')->get();

        return $items->contains(fn (OrderItem $item): bool => $item->returnableQuantity() > 0);
    }

    public function canRequestExchange(): bool
    {
        if (! $this->canRequestReturn()) {
            return false;
        }

        return $this->delivered_at->copy()->addDays((int) config('commerce.exchange_window_days', 30))->isFuture();
    }

    public function outstandingAmount(): float
    {
        $paid = (float) $this->payments()->where('status', 'paid')->sum('amount');

        return max(0, round((float) $this->grand_total - $paid, 2));
    }

    protected function casts(): array
    {
        return [
            'information' => 'array',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'shipping_method' => 'array',
            'payment_method' => 'array',
            'coupon_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'customization_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'rural_surcharge_total' => 'decimal:2',
            'product_shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
