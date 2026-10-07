<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'description',
    'image_path',
    'image_url',
    'is_default',
    'is_active',
    'sort_order',
])]
class ProductionMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function imageUrl(): ?string
    {
        return PublicMedia::url($this->image_path, $this->image_url);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function normalizedCode(): string
    {
        return Str::slug((string) $this->code);
    }
}
