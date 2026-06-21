<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'provider',
    'provider_reference',
    'brand',
    'last_four',
    'expiry_month',
    'expiry_year',
    'nickname',
    'billing_name',
    'is_default',
])]
class CustomerPaymentMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expiry_month' => 'integer',
            'expiry_year' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, CustomerPaymentMethod>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maskedLabel(): string
    {
        return strtoupper($this->brand) . ' ending in ' . $this->last_four;
    }

    public function expiryLabel(): string
    {
        return str_pad((string) $this->expiry_month, 2, '0', STR_PAD_LEFT) . '/' . substr((string) $this->expiry_year, -2);
    }
}
