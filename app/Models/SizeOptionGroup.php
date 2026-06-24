<?php

namespace App\Models;

use App\Enums\SizeAudience;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\PublicMedia;

#[Fillable([
    'name', 'slug', 'audience', 'description_html', 'chart_html', 'chart_title', 'chart_note',
    'chart_columns', 'chart_rows', 'chart_image_path', 'chart_image_url',
    'is_active', 'sort_order', 'created_by', 'updated_by',
])]
class SizeOptionGroup extends Model
{
    protected function casts(): array
    {
        return [
            'audience' => SizeAudience::class,
            'chart_columns' => 'array',
            'chart_rows' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param Builder<SizeOptionGroup> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<SizeOptionGroup> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(SizeOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function productGroups(): HasMany
    {
        return $this->hasMany(ProductSizeGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function chartImageUrl(): ?string
    {
        return PublicMedia::url($this->chart_image_path, $this->chart_image_url);
    }
}
