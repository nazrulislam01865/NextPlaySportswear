<?php

namespace Tests\Feature;

use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Models\RuralAreaSurcharge;
use App\Models\User;
use App\Services\Integrations\FlowTrack\FlowTrackInquiryPayloadFactory;
use App\Services\Shipping\RemoteAreaSurchargeNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoteAreaFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_records_are_classified_once_and_admin_lists_show_only_matching_remote_flags(): void
    {
        $this->remoteRule();
        $remoteQuote = $this->quote('BQ-REMOTE-001', '99601');
        $normalQuote = $this->quote('BQ-NORMAL-002', '10001');
        $remoteOrder = $this->order('NP-REMOTE-001', '99601');
        $normalOrder = $this->order('NP-NORMAL-002', '10001');

        $this->artisan('nextplay:backfill-remote-area-flags')->assertExitCode(0);
        $this->assertTrue($remoteQuote->fresh()->is_remote_area);
        $this->assertFalse($normalQuote->fresh()->is_remote_area);
        $this->assertTrue($remoteOrder->fresh()->is_remote_area);
        $this->assertFalse($normalOrder->fresh()->is_remote_area);

        // Re-running is safe and does not change classified snapshots.
        $this->artisan('nextplay:backfill-remote-area-flags')->assertExitCode(0);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->get(route('admin.orders.index'))
            ->assertOk()->assertSee('99601')->assertSee('Remote Area');
        $this->actingAs($admin, 'admin')->get(route('admin.bulk-quotes.index'))
            ->assertOk()->assertSee('99601')->assertSee('Remote Area');
        $this->actingAs($admin, 'admin')->get(route('admin.bulk-quotes.show', $remoteQuote))
            ->assertOk()->assertSee('Remote Area');
    }

    public function test_inquiry_flowtrack_payload_contains_saved_remote_flag_without_matching_again(): void
    {
        $quote = $this->quote('BQ-REMOTE-API', '99601');
        $quote->forceFill(['is_remote_area' => true])->saveQuietly();

        $payload = app(FlowTrackInquiryPayloadFactory::class)->make($quote->fresh());
        $this->assertTrue($payload['is_remote_area']);
        $this->assertSame('99601', $payload['postal_code']);
    }

    private function remoteRule(): void
    {
        $payload = app(RemoteAreaSurchargeNormalizer::class)->toModelPayload([
            'carrier' => 'UPS', 'country' => 'United States', 'iata_code' => 'US',
            'postal_code_low' => '99500', 'postal_code_high' => '99999',
            'city' => null, 'origin_surcharge' => 'No',
            'destination_surcharge' => 'Remote Area Surcharge',
            'extra_charge' => 20, 'is_active' => true,
        ]);
        RuralAreaSurcharge::query()->create($payload);
    }

    private function quote(string $reference, string $postal): BulkQuoteRequest
    {
        return BulkQuoteRequest::query()->create([
            'reference' => $reference,
            'full_name' => 'Example Customer', 'organization' => 'Example Club',
            'email' => 'test@example.com', 'phone' => '1112223333',
            'product_type' => 'Custom Jerseys', 'estimated_quantity' => '50-99',
            'sizes_needed' => 'Mixed', 'artwork_details' => 'Customer artwork',
            'shipping_address' => 'Sample Street', 'country' => 'United States',
            'postal_code' => $postal, 'needed_by' => '2026-12-05',
        ]);
    }

    private function order(string $number, string $postal): Order
    {
        return Order::query()->create([
            'order_number' => $number, 'customer_name' => 'Example Customer',
            'customer_email' => 'test@example.com',
            'shipping_address' => ['address' => ['postal_code' => $postal, 'country' => 'United States']],
        ]);
    }
}
