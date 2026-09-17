<?php

namespace Tests\Feature\Api\V1\Checkout;

use App\Models\RuralAreaSurcharge;
use App\Services\Checkout\CheckoutService;
use App\Services\Shipping\RemoteAreaSurchargeNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutApiState;
use Tests\TestCase;

class CheckoutTotalsApiTest extends TestCase
{
    use RefreshDatabase, BuildsCheckoutApiState;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bindCheckoutCatalog();
    }

    public function test_remote_area_surcharge_and_totals_match_existing_checkout_service(): void
    {
        $payload = app(RemoteAreaSurchargeNormalizer::class)->toModelPayload([
            'carrier' => 'UPS', 'country' => 'United States', 'iata_code' => 'US',
            'postal_code_low' => '99500', 'postal_code_high' => '99999', 'city' => null,
            'origin_surcharge' => 'No', 'destination_surcharge' => 'Remote Area Surcharge',
            'extra_charge' => 21.50, 'is_active' => true,
        ]);
        RuralAreaSurcharge::query()->create($payload);

        $customer = $this->checkoutCustomer();
        $this->authenticateCheckoutCustomer($customer);
        $this->addCheckoutItem(2);
        $this->completeInformation();
        $this->completeShipping(['postal_code' => '99601']);

        $api = $this->getJson('/api/v1/checkout')->assertOk();
        $bladeData = app(CheckoutService::class)->pageData($customer);

        $this->assertEqualsWithDelta(21.50, (float) $api->json('data.summary.rural_surcharge'), 0.001);
        $this->assertEqualsWithDelta((float) data_get($bladeData, 'summary.total'), (float) $api->json('data.summary.total'), 0.001);
        $this->assertEqualsWithDelta((float) data_get($bladeData, 'summary.shipping'), (float) $api->json('data.summary.shipping'), 0.001);
    }
}
