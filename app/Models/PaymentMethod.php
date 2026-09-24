<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'provider',
    'payment_type',
    'badge',
    'footer_icon_path',
    'footer_icon_alt',
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
    'show_in_footer',
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
            'show_in_footer' => 'boolean',
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

    public function footerIconUrl(): ?string
    {
        return PublicMedia::url($this->footer_icon_path);
    }
}
