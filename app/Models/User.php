<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'company_name', 'preferred_sport', 'marketing_consent', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return HasMany<CustomerAddress>
     */
    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->latest();
    }

    /**
     * @return HasMany<CustomerPaymentMethod>
     */
    public function customerPaymentMethods(): HasMany
    {
        return $this->hasMany(CustomerPaymentMethod::class)->latest();
    }


    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'catalog_manager'], true) && $this->is_active;
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer' && $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'marketing_consent' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
