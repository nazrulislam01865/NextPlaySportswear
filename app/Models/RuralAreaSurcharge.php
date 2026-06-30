<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'country',
    'state',
    'postal_code_patterns',
    'amount',
    'is_active',
])]
class RuralAreaSurcharge extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function patternList(): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $this->postal_code_patterns))
            ->map(fn ($pattern): string => trim((string) $pattern))
            ->filter()
            ->values()
            ->all();
    }
}
