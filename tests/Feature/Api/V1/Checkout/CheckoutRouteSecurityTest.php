<?php

namespace Tests\Feature\Api\V1\Checkout;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CheckoutRouteSecurityTest extends TestCase
{
    public function test_checkout_api_is_customer_verified_session_only_and_mutations_are_throttled(): void
    {
        $show = Route::getRoutes()->getByName('api.v1.checkout.show');
        $shipping = Route::getRoutes()->getByName('api.v1.checkout.shipping-address.update');
        $review = Route::getRoutes()->getByName('api.v1.checkout.review.update');

        $this->assertNotNull($show);
        foreach (['web', 'auth:web', 'customer', 'verified'] as $middleware) {
            $this->assertContains($middleware, $show->gatherMiddleware());
        }

        $this->assertContains('throttle:checkout-step', $shipping->gatherMiddleware());
        $this->assertContains('throttle:checkout-step', $review->gatherMiddleware());
    }
}
