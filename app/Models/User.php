<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\AdminRbac;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /** @return HasMany<ShoppingCart> */
    public function shoppingCarts(): HasMany
    {
        return $this->hasMany(ShoppingCart::class)->latest('updated_at');
    }

    public function activeShoppingCart(): HasMany
    {
        return $this->shoppingCarts()->where('status', 'active');
    }

    /** @return HasMany<Order> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    /** @return HasMany<OrderReturnRequest> */
    public function orderReturnRequests(): HasMany
    {
        return $this->hasMany(OrderReturnRequest::class)->latest('requested_at');
    }

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role', 'slug');
    }

    public function isAdmin(): bool
    {
        return $this->is_active && AdminRbac::roleIsAdmin($this->role);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer' && $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' && $this->is_active;
    }

    public function canManageOrders(): bool
    {
        return $this->canAdmin('orders.manage');
    }

    public function canAdmin(string $permissionKey): bool
    {
        return AdminRbac::userCan($this, $permissionKey);
    }

    public function canDeleteAdminRecords(): bool
    {
        return $this->canAdmin(AdminRbac::DELETE_PERMISSION_KEY);
    }

    public function adminRoleLabel(): string
    {
        return AdminRbac::roleLabel($this->role);
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
