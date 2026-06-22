<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceTier extends Model
{
    protected $fillable = ['product_id', 'label', 'minimum_quantity', 'maximum_quantity', 'unit_price', 'compare_at_price', 'savings_label', 'sort_order'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'compare_at_price' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
