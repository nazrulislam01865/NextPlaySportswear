<?php

namespace Tests\Feature\Storefront;

use App\Services\Checkout\CheckoutService;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class CheckoutPrototypeFlowTest extends TestCase
{
    public function test_checkout_steps_put_review_before_hosted_stripe_payment(): void
    {
        $method = new ReflectionMethod(CheckoutService::class, 'steps');
        $steps = $method->invoke(app(CheckoutService::class));

        $this->assertSame(['information', 'shipping', 'billing', 'review'], array_column($steps, 'key'));
        $this->assertSame(['Contact', 'Shipping', 'Billing', 'Review'], array_column($steps, 'label'));
    }

    public function test_review_is_the_final_on_site_step_and_stripe_is_hosted(): void
    {
        $this->assertTrue(Route::has('checkout.place-order.submit'));
        $review = file_get_contents(resource_path('views/storefront/checkout/review.blade.php'));
        $summary = file_get_contents(resource_path('views/components/storefront/checkout/summary-card.blade.php'));
        $shipping = file_get_contents(resource_path('views/storefront/checkout/shipping-address.blade.php'));
        $field = file_get_contents(resource_path('views/components/storefront/checkout/form-field.blade.php'));
        $this->assertStringContainsString('order-idempotency-key', $review);
        $this->assertStringContainsString("route('checkout.place-order.submit')", $summary);
        $this->assertStringNotContainsString('Check address & charges', $shipping);
        $this->assertStringNotContainsString('name="card_number"', $review);
        $this->assertStringNotContainsString('name="cvv"', $summary);
        $this->assertStringContainsString("@error($".'name)', $field);
    }

    public function test_delivery_estimate_comes_from_selected_fulfillment_lines(): void
    {
        $summary = file_get_contents(resource_path('views/components/storefront/checkout/summary-card.blade.php'));
        $this->assertStringContainsString("'fulfillment_lines'", $summary);
        $this->assertStringContainsString('estimated_minimum_days', $summary);
        $this->assertStringContainsString('estimated_maximum_days', $summary);
    }
    public function test_selected_product_production_and_shipping_create_the_delivery_range(): void
    {
        $method = new ReflectionMethod(CheckoutService::class, 'fulfillmentLines');
        $cart = ['items' => [[
            'key' => 'configured-jersey', 'quantity' => 5,
            'product' => ['title' => 'Selected jersey'],
            'customization' => ['fulfillment' => [
                'production' => ['minimum_days' => 4, 'maximum_days' => 6],
                'shipping' => ['minimum_days' => 5, 'maximum_days' => 7],
            ]],
        ]]];

        $lines = $method->invoke(app(CheckoutService::class), $cart, ['lines' => []], ['lines' => []]);
        $this->assertSame(9, $lines[0]['estimated_minimum_days']);
        $this->assertSame(13, $lines[0]['estimated_maximum_days']);
    }
}
