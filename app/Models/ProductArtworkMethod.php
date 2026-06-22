<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductArtworkMethod extends Model
{
    protected $fillable = ['product_id', 'name', 'code', 'icon', 'description', 'price_adjustment', 'requires_upload', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price_adjustment' => 'decimal:2', 'requires_upload' => 'boolean', 'is_active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
