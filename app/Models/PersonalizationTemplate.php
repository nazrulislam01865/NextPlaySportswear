<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PersonalizationTemplate extends Model
{
    protected $fillable = [
        'category_id', 'name', 'section_title', 'description', 'shared_entry_label',
        'shared_entry_enabled', 'is_active', 'is_published', 'fields',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'shared_entry_enabled' => 'boolean',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_personalization_template');
    }

    public function getUsableAttribute(): bool
    {
        return $this->is_published && $this->is_active && collect($this->fields)->contains(fn ($field) => ($field['enabled'] ?? true));
    }
}
