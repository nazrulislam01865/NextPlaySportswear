<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProductSizeGroup extends Model
{
    protected $fillable = [
        'product_id', 'name', 'code', 'chart_enabled', 'chart_title', 'chart_note',
        'chart_columns', 'chart_rows', 'chart_image_path', 'chart_image_url', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'chart_enabled' => 'boolean',
            'chart_columns' => 'array',
            'chart_rows' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('sort_order');
    }

    public function chartImageUrl(): ?string
    {
        if ($this->chart_image_url) {
            return $this->chart_image_url;
        }

        return $this->chart_image_path ? Storage::disk('public')->url($this->chart_image_path) : null;
    }
}
