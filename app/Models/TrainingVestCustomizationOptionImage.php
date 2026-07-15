<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'training_vest_customization_option_id',
    'name',
    'image_path',
    'image_url',
    'is_primary',
    'sort_order',
])]
class TrainingVestCustomizationOptionImage extends Model
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
        return $this->belongsTo(TrainingVestCustomizationOption::class, 'training_vest_customization_option_id');
    }

    public function publicUrl(): ?string
    {
        return PublicMedia::url($this->image_path, $this->image_url);
    }
}
