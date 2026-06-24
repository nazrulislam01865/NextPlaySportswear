<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\PublicMedia;

class ProductSizeGroup extends Model
{
    protected $fillable = [
        'product_id', 'size_option_group_id', 'name', 'code', 'description_html', 'chart_html', 'chart_enabled', 'chart_title', 'chart_note',
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

    public function masterGroup(): BelongsTo
    {
        return $this->belongsTo(SizeOptionGroup::class, 'size_option_group_id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('sort_order');
    }

    public function chartImageUrl(): ?string
    {
        return PublicMedia::url($this->chart_image_path, $this->chart_image_url);
    }
}
