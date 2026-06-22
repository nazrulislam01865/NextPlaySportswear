<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductOptionValue extends Model
{
    protected $fillable = [
        'product_option_group_id', 'label', 'code', 'description', 'color_hex', 'image_path',
        'image_url', 'price_adjustment', 'stock_quantity', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'product_option_group_id');
    }

    public function publicImageUrl(): ?string
    {
        if ($this->image_url) {
            return $this->image_url;
        }

        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
