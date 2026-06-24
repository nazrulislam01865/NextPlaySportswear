<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\PublicMedia;

#[Fillable([
    'jersey_customization_option_id',
    'name',
    'image_path',
    'image_url',
    'is_primary',
    'sort_order',
])]
class JerseyCustomizationOptionImage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(JerseyCustomizationOption::class, 'jersey_customization_option_id');
    }

    public function publicUrl(): ?string
    {
        return PublicMedia::url($this->image_path, $this->image_url);
    }
}
