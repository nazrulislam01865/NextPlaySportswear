<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CategoryContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'block_type', 'heading', 'subheading', 'content_html', 'image_path', 'image_url',
        'image_alt', 'button_label', 'button_url', 'settings', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function publicImageUrl(): ?string
    {
        if (filled($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }

        return filled($this->image_url) ? $this->image_url : null;
    }
}
