<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutTemporarilyDisabledTest extends TestCase
{
    public function test_checkout_pages_redirect_to_cart_while_paused(): void
    {
        $this->assertFalse(config('storefront.checkout_enabled'), 'Checkout must be off by default, even when the server .env previously enabled it.');

        foreach (['checkout.index', 'checkout.information', 'checkout.shipping-address', 'checkout.billing-address', 'checkout.shipping-method', 'checkout.payment-method', 'checkout.review', 'checkout.place-order', 'checkout.referral'] as $route) {
            $this->get(route($route))
                ->assertRedirect(route('cart.index'))
                ->assertSessionHas('status');
        }
    }

    public function test_checkout_post_requests_cannot_create_an_order_while_paused(): void
    {
        $this->assertFalse(config('storefront.checkout_enabled'));

        // Exercise the pause with a valid CSRF token. This also works when the
        // developer ran config:cache before tests and CSRF remains enabled.
        $token = 'checkout-disabled-test-token';
        $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token);

        foreach (['checkout.information.store', 'checkout.shipping-address.store', 'checkout.billing-address.store', 'checkout.shipping-method.store', 'checkout.payment-method.store', 'checkout.review.store', 'checkout.place-order.submit', 'checkout.referral.store'] as $route) {
            $this->post(route($route), [])->assertStatus(503);
        }
    }

    public function test_existing_checkout_success_route_is_not_suspended(): void
    {
        config()->set('storefront.checkout_enabled', false);

        // The existing authentication middleware still applies to this route.
        $this->get(route('checkout.success'))
            ->assertRedirect(route('login'));
    }

    public function test_checkout_middleware_passes_requests_when_reenabled(): void
    {
        config()->set('storefront.checkout_enabled', true);

        // An unauthenticated visitor reaches normal checkout authentication, not the pause redirect.
        $this->get(route('checkout.index'))
            ->assertRedirect(route('login'));
    }
}
