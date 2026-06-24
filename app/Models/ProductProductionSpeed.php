<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductProductionSpeed extends Model
{
    protected $fillable = ['product_id', 'name', 'code', 'description', 'price_adjustment', 'minimum_quantity', 'maximum_quantity', 'minimum_days', 'maximum_days', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'minimum_quantity' => 'integer',
            'maximum_quantity' => 'integer',
            'minimum_days' => 'integer',
            'maximum_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
