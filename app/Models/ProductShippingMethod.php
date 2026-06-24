<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductShippingMethod extends Model
{
    protected $fillable = [
        'product_id', 'name', 'code', 'description', 'price_adjustment', 'charge_type',
        'minimum_days', 'maximum_days', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
