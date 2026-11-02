<?php

namespace Tests\Feature\Storefront;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StripeCheckoutContentSecurityPolicyTest extends TestCase
{
    public function test_customer_checkout_form_pages_allow_only_the_stripe_checkout_destination(): void
    {
        foreach (['checkout.review', 'checkout.payment-method', 'account.orders.pay'] as $name) {
            $header = $this->policyFor($name);

            $this->assertStringContainsString(
                "form-action 'self' https://checkout.stripe.com",
                $header,
                $name
            );
            $this->assertStringContainsString("frame-ancestors 'none'", $header);
        }
    }

    public function test_other_pages_remain_restricted_to_same_origin_forms(): void
    {
        $header = $this->policyFor('storefront.home');

        $this->assertStringContainsString("form-action 'self'", $header);
        $this->assertStringNotContainsString('checkout.stripe.com', $header);
    }

    public function test_post_responses_do_not_expand_the_form_action_policy(): void
    {
        $header = $this->policyFor('checkout.place-order.submit', 'POST');

        $this->assertStringContainsString("form-action 'self'", $header);
        $this->assertStringNotContainsString('checkout.stripe.com', $header);
    }

    private function policyFor(string $routeName, string $method = 'GET'): string
    {
        $previousEnvironment = $this->app->environment();
        $this->app->instance('env', 'production');

        try {
            $request = Request::create('https://www.nextplaysportswear.com/test', $method);
            $route = new Route($method, '/test', static fn () => new Response('OK'));
            $route->name($routeName);
            $request->setRouteResolver(static fn () => $route);

            $response = (new SecurityHeaders())->handle(
                $request,
                static fn () => new Response('OK')
            );

            return (string) $response->headers->get('Content-Security-Policy');
        } finally {
            $this->app->instance('env', $previousEnvironment);
        }
    }
}
