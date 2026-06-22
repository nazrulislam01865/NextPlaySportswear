<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomepageSlide extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'eyebrow', 'title', 'description', 'image_path', 'image_url', 'image_alt',
        'image_focal_position', 'show_content', 'show_eyebrow', 'show_title',
        'show_description', 'show_primary_button', 'primary_label', 'primary_url',
        'primary_target', 'show_secondary_button', 'secondary_label', 'secondary_url',
        'secondary_target', 'content_position', 'text_alignment', 'text_theme',
        'overlay_color', 'overlay_opacity', 'is_active', 'sort_order', 'starts_at',
        'ends_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'show_content' => 'boolean',
            'show_eyebrow' => 'boolean',
            'show_title' => 'boolean',
            'show_description' => 'boolean',
            'show_primary_button' => 'boolean',
            'show_secondary_button' => 'boolean',
            'is_active' => 'boolean',
            'overlay_opacity' => 'integer',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeStorefrontVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
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
