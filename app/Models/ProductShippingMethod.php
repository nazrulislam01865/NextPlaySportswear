<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductShippingMethod extends Model
{
    protected $fillable = [
        'product_id', 'shipping_method_id', 'name', 'code', 'description', 'price_adjustment', 'charge_type', 'charge_application',
        'base_price', 'per_item_price', 'free_shipping_minimum', 'minimum_days', 'maximum_days',
        'starts_after_artwork_approval', 'is_quote_based', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'charge_application' => 'string',
            'base_price' => 'decimal:2',
            'per_item_price' => 'decimal:2',
            'free_shipping_minimum' => 'decimal:2',
            'minimum_days' => 'integer',
            'maximum_days' => 'integer',
            'starts_after_artwork_approval' => 'boolean',
            'is_quote_based' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
