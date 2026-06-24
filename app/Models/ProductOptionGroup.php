<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOptionGroup extends Model
{
    protected $fillable = [
        'product_id', 'name', 'code', 'section', 'type', 'display_mode', 'fixed_value_code',
        'fixed_text_value', 'show_in_summary', 'use_as_filter', 'catalog_attribute_id', 'description', 'placeholder',
        'is_required', 'minimum_selections', 'maximum_selections', 'accepted_file_types',
        'maximum_file_size_mb', 'validation_rules', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'show_in_summary' => 'boolean',
            'use_as_filter' => 'boolean',
            'validation_rules' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function catalogAttribute(): BelongsTo
    {
        return $this->belongsTo(CatalogAttribute::class, 'catalog_attribute_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('sort_order');
    }
}
