<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductFabricPriceTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'jersey_customization_option_id',
        'fabric_key',
        'fabric_code',
        'fabric_label',
        'price_table_headers',
        'price_table_rows',
        'price_table_ranges',
        'price_table_highlight_column',
        'price_table_note',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_table_headers' => 'array',
            'price_table_rows' => 'array',
            'price_table_ranges' => 'array',
            'price_table_highlight_column' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function jerseyCustomizationOption(): BelongsTo
    {
        return $this->belongsTo(JerseyCustomizationOption::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(ProductFabricPriceTier::class)->orderBy('minimum_quantity');
    }
}
