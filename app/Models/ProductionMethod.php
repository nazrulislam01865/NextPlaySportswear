<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'description',
    'minimum_days',
    'maximum_days',
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
            'minimum_days' => 'integer',
            'maximum_days' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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
