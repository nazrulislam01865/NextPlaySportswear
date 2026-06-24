<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Support\PublicMedia;

class CatalogAttributeValue extends Model
{
    use HasFactory;

    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_id', 'label', 'slug', 'color_hex', 'image_path', 'image_url', 'numeric_value',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'numeric_value' => 'decimal:4',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(CatalogAttribute::class, 'attribute_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'attribute_value_product', 'attribute_value_id', 'product_id')
            ->withPivot('sort_order');
    }

    public function publicImageUrl(): ?string
    {
        return PublicMedia::url($this->image_path, $this->image_url);
    }
}
