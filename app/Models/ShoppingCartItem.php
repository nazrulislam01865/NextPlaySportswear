<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopping_cart_id',
        'product_id',
        'product_slug',
        'item_key',
        'quantity',
        'unit_price',
        'customization_unit_price',
        'line_subtotal',
        'customization_total',
        'line_total',
        'customization',
        'product_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'customization' => 'array',
            'product_snapshot' => 'array',
            'unit_price' => 'decimal:2',
            'customization_unit_price' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'customization_total' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(ShoppingCart::class, 'shopping_cart_id');
    }
}
