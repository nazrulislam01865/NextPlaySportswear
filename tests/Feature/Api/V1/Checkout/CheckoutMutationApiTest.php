<?php

namespace Tests\Feature\Api\V1\Checkout;

use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutApiState;
use Tests\TestCase;

class CheckoutMutationApiTest extends TestCase
{
    use RefreshDatabase, BuildsCheckoutApiState;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bindCheckoutCatalog();
    }

    public function test_checkout_mutations_progress_through_review_without_raw_payment_data(): void
    {
        $customer = $this->checkoutCustomer();
        $this->authenticateCheckoutCustomer($customer);
        $this->addCheckoutItem();
        $this->completeInformation();
        $this->completeShipping();
        $this->completeBilling();

        $this->putJson('/api/v1/checkout/payment-method', [
            'payment_method' => 'invoice',
            'card_number' => '4242424242424242',
            'cvv' => '123',
        ])->assertUnprocessable();

        $payment = $this->putJson('/api/v1/checkout/payment-method', [
            'payment_method' => 'invoice',
        ])->assertOk();

        $this->assertStringNotContainsString('4242424242424242', $payment->getContent());
        $this->assertStringNotContainsString('"cvv"', $payment->getContent());

        $this->getJson('/api/v1/checkout/review')->assertOk();
        $this->putJson('/api/v1/checkout/review', ['confirm_details' => true])
            ->assertOk()
            ->assertJsonPath('data.steps.4.complete', true);
    }

    public function test_saved_new_address_mutation_can_be_replayed_with_idempotency_key_without_duplicate_record(): void
    {
        $customer = $this->checkoutCustomer();
        $this->authenticateCheckoutCustomer($customer);
        $this->addCheckoutItem();
        $this->completeInformation();

        $payload = [
            'address_choice' => 'new',
            'first_name' => 'Checkout',
            'last_name' => 'Customer',
            'address_line_1' => '100 Main Street',
            'city' => 'Austin',
            'state' => 'Texas',
            'country' => 'United States',
            'postal_code' => '78701',
            'phone' => '+15555550123',
            'save_to_account' => true,
        ];
        $headers = ['Idempotency-Key' => 'checkout-shipping-0001'];

        $this->withHeaders($headers)->putJson('/api/v1/checkout/shipping-address', $payload)->assertOk();
        $this->withHeaders($headers)->putJson('/api/v1/checkout/shipping-address', $payload)
            ->assertOk()->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(1, $customer->customerAddresses()->count());
    }

    public function test_saved_shipping_address_must_belong_to_current_customer(): void
    {
        $customer = $this->checkoutCustomer();
        $other = $this->checkoutCustomer(['email' => 'other@example.com']);
        $foreign = CustomerAddress::query()->create([
            'user_id' => $other->id, 'type' => 'shipping', 'first_name' => 'Other', 'last_name' => 'Owner',
            'address_line_1' => '500 Foreign Road', 'city' => 'Dallas', 'state' => 'Texas',
            'country' => 'United States', 'postal_code' => '75001', 'phone' => '+15555550000', 'is_default' => true,
        ]);

        $this->authenticateCheckoutCustomer($customer);
        $this->addCheckoutItem();
        $this->completeInformation();

        $this->putJson('/api/v1/checkout/shipping-address', [
            'address_choice' => 'saved:'.$foreign->id,
        ])->assertUnprocessable();
    }
}
