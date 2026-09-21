<?php

namespace App\Services\Storefront;

use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Wishlist\WishlistHeaderService;

class StorefrontBootstrapService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly WishlistHeaderService $wishlist,
        private readonly StorefrontSettingsService $settings,
    ) {
    }

    /** @return array<string, mixed> */
    public function get(?User $customer): array
    {
        $customer = $customer?->isCustomer() ? $customer : null;
        $cart = $this->cart->headerSummary(4);
        $wishlist = $this->wishlist->summary($customer, 4);
        $header = $this->settings->header();
        $branding = (array) ($header['branding'] ?? []);
        $logo = trim((string) ($branding['logo'] ?? ''));
        $logoAlt = trim((string) ($branding['logo_alt'] ?? ''));

        return [
            'site' => [
                'name' => (string) config('storefront.name'),
                'tagline' => (string) config('storefront.tagline'),
                'logo' => $logo !== '' ? $logo : (string) config('storefront.vue_logo'),
                'logo_alt' => $logoAlt !== '' ? $logoAlt : (string) config('storefront.name'),
            ],
            'customer' => $customer ? [
                'id' => (int) $customer->getKey(),
                'name' => (string) $customer->name,
                'email' => (string) $customer->email,
                'email_verified' => $customer->hasVerifiedEmail(),
            ] : null,
            'cart' => [
                'quantity' => (int) ($cart['quantity'] ?? 0),
                'total_items' => (int) ($cart['total_items'] ?? 0),
                'total' => (float) ($cart['total'] ?? 0),
            ],
            'wishlist' => [
                'total_items' => (int) ($wishlist['total_items'] ?? 0),
            ],
            'navigation' => $this->settings->navigation(),
            'header' => $header,
            'footer' => $this->settings->footer(),
        ];
    }
}
