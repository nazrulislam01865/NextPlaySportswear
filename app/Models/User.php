<?php

namespace App\Models;

use App\Services\Email\TransactionalEmailManager;
use App\Support\AdminRbac;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'company_name', 'preferred_sport', 'marketing_consent', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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

    /** @return HasMany<ProductWishlist> */
    public function productWishlists(): HasMany
    {
        return $this->hasMany(ProductWishlist::class)->latest();
    }

    /** @return HasMany<Order> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    /** @return HasMany<BulkQuoteRequest> */
    public function bulkQuoteRequests(): HasMany
    {
        return $this->hasMany(BulkQuoteRequest::class)->latest();
    }

    /** @return HasMany<OrderReturnRequest> */
    public function orderReturnRequests(): HasMany
    {
        return $this->hasMany(OrderReturnRequest::class)->latest('requested_at');
    }

    /** @return HasMany<RewardTransaction> */
    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class)->latest();
    }

    public function rewardProfile(): HasOne
    {
        return $this->hasOne(CustomerRewardProfile::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    /** @return HasMany<CustomerReferral> */
    public function referralsMade(): HasMany
    {
        return $this->hasMany(CustomerReferral::class, 'referrer_id')->latest();
    }

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role', 'slug');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'suspended_by');
    }

    public function reactivatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reactivated_by');
    }

    public function sendEmailVerificationNotification(): void
    {
        app(TransactionalEmailManager::class)->emailVerification($this);
    }

    /**
     * Keep Laravel's verified middleware reversible while verification is
     * temporarily muted. Customers created while the switch is disabled are
     * still persisted with email_verified_at, but this also prevents legacy
     * unverified customer sessions from being trapped on the verification
     * notice until they next log in and receive a persisted timestamp.
     */
    public function hasVerifiedEmail(): bool
    {
        if ($this->isCustomer() && ! config('security.email_verification.enabled', false)) {
            return true;
        }

        return $this->email_verified_at !== null;
    }

    /**
     * email
     */
    public function sendPasswordResetNotification(
        $token
    ): void {
        app(
            TransactionalEmailManager::class
        )->passwordReset(
            $this,
            (string) $token
        );
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
            'welcome_email_sent_at' => 'datetime',
            'password' => 'hashed',
            'marketing_consent' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'auth_session_version' => 'integer',
            'suspended_at' => 'datetime',
            'reactivated_at' => 'datetime',
        ];
    }
}
