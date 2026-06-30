<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'type',
    'first_name',
    'last_name',
    'company_name',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'country',
    'postal_code',
    'phone',
    'email',
    'delivery_instruction',
    'is_default',
])]
class CustomerAddress extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, CustomerAddress>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'billing' => 'Billing',
            'shipping' => 'Shipping',
            default => 'Billing & Shipping',
        };
    }
}
