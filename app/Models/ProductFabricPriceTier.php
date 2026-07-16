<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFabricPriceTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_fabric_price_table_id',
        'label',
        'minimum_quantity',
        'maximum_quantity',
        'unit_price',
        'compare_at_price',
        'savings_label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'minimum_quantity' => 'integer',
            'maximum_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(ProductFabricPriceTable::class, 'product_fabric_price_table_id');
    }
}
