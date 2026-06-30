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
            'is_quote_based' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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
