<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogAttribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attributes';

    protected $fillable = [
        'name', 'slug', 'display_type', 'unit', 'is_filterable', 'is_searchable', 'is_active',
        'sort_order', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_searchable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CatalogAttributeValue::class, 'attribute_id')->orderBy('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_filters', 'attribute_id', 'category_id')
            ->withPivot(['label', 'is_expanded', 'sort_order']);
    }
}
