<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPaymentInstallment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['sequence' => 'integer', 'amount_minor' => 'integer', 'due_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Approved installment amounts cannot be edited.'));
        static::deleting(fn () => throw new \LogicException('Approved installment amounts cannot be deleted.'));
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(OrderInstallmentSchedule::class, 'order_installment_schedule_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_payment_installment_id');
    }
}
