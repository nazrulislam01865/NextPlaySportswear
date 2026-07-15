<?php

namespace App\Models;

use App\Enums\TrainingVestCustomizationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'type',
    'name',
    'slug',
    'color_hex',
    'description',
    'is_active',
    'sort_order',
    'created_by',
    'updated_by',
])]
class TrainingVestCustomizationOption extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => TrainingVestCustomizationType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param Builder<TrainingVestCustomizationOption> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('type')->orderBy('sort_order')->orderBy('name');
    }

    /** @param Builder<TrainingVestCustomizationOption> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TrainingVestCustomizationOptionImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(TrainingVestCustomizationOptionImage::class)
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
