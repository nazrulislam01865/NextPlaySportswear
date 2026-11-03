<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderInstallmentSchedule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['total_minor' => 'integer', 'accepted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Approved installment contracts cannot be edited.'));
        static::deleting(fn () => throw new \LogicException('Approved installment contracts cannot be deleted.'));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(OrderPaymentInstallment::class)->orderBy('sequence');
    }
}
