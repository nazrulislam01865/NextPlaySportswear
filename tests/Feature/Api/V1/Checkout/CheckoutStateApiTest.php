<?php

namespace Tests\Feature\Api\V1\Checkout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutApiState;
use Tests\TestCase;

class CheckoutStateApiTest extends TestCase
{
    use RefreshDatabase, BuildsCheckoutApiState;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bindCheckoutCatalog();
    }

    public function test_empty_cart_is_machine_readable_conflict(): void
    {
        $customer = $this->checkoutCustomer();
        $this->authenticateCheckoutCustomer($customer);

        $this->getJson('/api/v1/checkout')
            ->assertStatus(409)
            ->assertJsonPath('code', 'checkout_cart_empty');
    }

    public function test_unverified_customer_cannot_enter_checkout_api(): void
    {
        $customer = $this->checkoutCustomer(['email_verified_at' => null]);
        $this->authenticateCheckoutCustomer($customer);
        $this->addCheckoutItem();

        $this->getJson('/api/v1/checkout')->assertForbidden();
    }

    public function test_checkout_state_exposes_steps_and_first_incomplete_prerequisite(): void
    {
        $customer = $this->checkoutCustomer();
        $this->authenticateCheckoutCustomer($customer);
        $this->addCheckoutItem();

        $this->getJson('/api/v1/checkout')
            ->assertOk()
            ->assertJsonPath('data.first_incomplete_step.key', 'information')
            ->assertJsonPath('data.current_step', 'information')
            ->assertJsonPath('data.steps.0.key', 'information')
            ->assertJsonPath('data.steps.0.complete', false)
            ->assertJsonPath('data.summary.quantity', 2);
    }

    public function test_checkout_cannot_skip_required_prerequisites(): void
    {
        $customer = $this->checkoutCustomer();
        $this->authenticateCheckoutCustomer($customer);
        $this->addCheckoutItem();

        $this->putJson('/api/v1/checkout/shipping-address', [
            'address_choice' => 'new', 'first_name' => 'Alpha', 'last_name' => 'Beta',
            'address_line_1' => '100 Main Street', 'city' => 'Austin', 'state' => 'Texas',
            'country' => 'United States', 'postal_code' => '78701', 'phone' => '+15555550123',
        ])->assertStatus(409)
          ->assertJsonPath('code', 'checkout_step_incomplete')
          ->assertJsonPath('data.required_step', 'information')
          ->assertJsonPath('data.requested_step', 'shipping');
    }
}
