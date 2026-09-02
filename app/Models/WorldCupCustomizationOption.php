<?php

namespace App\Models;

use App\Enums\WorldCupCustomizationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'category_key', 'type', 'name', 'slug', 'description', 'is_active', 'sort_order', 'created_by', 'updated_by',
])]
class WorldCupCustomizationOption extends Model
{
    protected function casts(): array
    {
        return [
            'type' => WorldCupCustomizationType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param Builder<WorldCupCustomizationOption> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('category_key')->orderBy('type')->orderBy('sort_order')->orderBy('name');
    }

    /** @param Builder<WorldCupCustomizationOption> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(WorldCupCustomizationOptionImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(WorldCupCustomizationOptionImage::class)
            ->where('is_primary', true)
            ->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
