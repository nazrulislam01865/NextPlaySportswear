<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'provider',
    'payment_type',
    'badge',
    'description',
    'instructions',
    'minimum_total',
    'maximum_total',
    'is_online',
    'requires_provider_redirect',
    'requires_manual_review',
    'allows_saved_methods',
    'is_default',
    'is_active',
    'sort_order',
])]
class PaymentMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'minimum_total' => 'decimal:2',
            'maximum_total' => 'decimal:2',
            'is_online' => 'boolean',
            'requires_provider_redirect' => 'boolean',
            'requires_manual_review' => 'boolean',
            'allows_saved_methods' => 'boolean',
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
