<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSize extends Model
{
    protected $fillable = ['product_size_group_id', 'label', 'code', 'price_adjustment', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price_adjustment' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductSizeGroup::class, 'product_size_group_id');
    }
}
