<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'description',
    'charge_amount',
    'charge_application',
    'base_price',
    'per_item_price',
    'free_shipping_minimum',
    'minimum_quantity',
    'maximum_quantity',
    'minimum_subtotal',
    'maximum_subtotal',
    'country',
    'state',
    'minimum_days',
    'maximum_days',
    'starts_after_artwork_approval',
    'is_quote_based',
    'is_default',
    'is_active',
    'sort_order',
])]
class ShippingMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'charge_amount' => 'decimal:2',
            'base_price' => 'decimal:2',
            'per_item_price' => 'decimal:2',
            'free_shipping_minimum' => 'decimal:2',
            'minimum_subtotal' => 'decimal:2',
            'maximum_subtotal' => 'decimal:2',
            'minimum_quantity' => 'integer',
            'maximum_quantity' => 'integer',
            'minimum_days' => 'integer',
            'maximum_days' => 'integer',
            'starts_after_artwork_approval' => 'boolean',
            'charge_application' => 'string',
            'is_quote_based' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }



    public static function chargeApplicationOptions(): array
    {
        return [
            'included' => 'Included / no extra charge',
            'per_order' => 'Per order',
            'per_product' => 'Per product',
            'per_item' => 'Per item',
        ];
    }

    public function chargeApplicationLabel(): string
    {
        return self::chargeApplicationOptions()[$this->chargeApplication()] ?? 'Per order';
    }

    public function chargeApplication(): string
    {
        $application = (string) ($this->getAttribute('charge_application') ?: '');

        if (array_key_exists($application, self::chargeApplicationOptions())) {
            return $application;
        }

        if ((float) ($this->getAttribute('base_price') ?? 0) > 0) {
            return 'per_order';
        }

        return ((float) ($this->getAttribute('per_item_price') ?? 0) > 0) ? 'per_item' : 'included';
    }

    public function effectiveChargeAmount(): float
    {
        $amount = $this->getAttribute('charge_amount');

        if ($amount !== null) {
            return max(0, (float) $amount);
        }

        if ($this->chargeApplication() === 'per_order') {
            return max(0, (float) ($this->getAttribute('base_price') ?? 0));
        }

        if ($this->chargeApplication() === 'per_item') {
            return max(0, (float) ($this->getAttribute('per_item_price') ?? 0));
        }

        return 0.00;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function normalizedCode(): string
    {
        return Str::slug((string) $this->code);
    }
}
