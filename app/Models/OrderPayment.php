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
    'order_payment_installment_id', 'last_reconciled_at', 'reconciliation_failures',
])]
class OrderPayment extends Model
{
    /** Protect posted provider amounts and confirmed financial references. */
    protected static function booted(): void
    {
        static::updating(function (self $payment): void {
            $posted = filled($payment->getOriginal('provider_session_id'))
                || $payment->getOriginal('status') === 'paid';
            if ($posted && $payment->isDirty([
                'order_id', 'payment_method_id', 'gateway', 'provider',
                'idempotency_key', 'amount', 'currency', 'order_payment_installment_id',
            ])) {
                throw new \LogicException('An established payment amount and identity cannot be modified.');
            }
            if ($payment->getOriginal('status') === 'paid' && $payment->isDirty([
                'status', 'paid_at', 'provider_session_id', 'provider_payment_id', 'provider_reference',
            ])) {
                throw new \LogicException('A confirmed receipt cannot be rewritten or downgraded.');
            }
        });
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(OrderPaymentInstallment::class, 'order_payment_installment_id');
    }

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
            'last_reconciled_at' => 'datetime',
            'reconciliation_failures' => 'integer',
        ];
    }
}
