<?php

namespace Tests\Feature\Integrations;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowTrackOrderProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'only-for-server-tests');
    }

    private function order(): Order
    {
        return Order::query()->create([
            'order_number' => 'NP-TEST-105',
            'status' => 'pending_payment', 'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled', 'currency' => 'USD',
            'customer_name' => 'Taylor Buyer', 'customer_email' => 'buyer@example.test',
            'subtotal' => 100, 'customization_total' => 0, 'discount_total' => 0,
            'shipping_total' => 0, 'tax_total' => 0, 'grand_total' => 100,
            'total_quantity' => 1, 'placed_at' => now(),
        ]);
    }

    public function test_callback_requires_server_token_and_never_updates_payment_state(): void
    {
        $order = $this->order();
        $payload = [
            'source_order_id' => $order->id, 'flow_job_id' => 876,
            'status' => 'InProgress', 'stage' => 'Production', 'progress' => 45,
            'changed_at' => now()->subMinutes(2)->utc()->format('Y-m-d\TH:i:sP'),
        ];
        $url = route('api.flowtrack.orders.progress', ['orderNumber' => $order->order_number]);
        $this->postJson($url, $payload)->assertUnauthorized();
        $this->withToken('only-for-server-tests')->postJson($url, $payload)->assertOk()->assertJsonPath('applied', true);
        $order->refresh();
        $this->assertSame('Production', $order->flowtrack_operational_stage);
        $this->assertSame(45, $order->flowtrack_operational_progress);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending_payment', $order->status);
        $this->withToken('only-for-server-tests')->postJson($url, $payload)->assertOk()->assertJsonPath('applied', false);
    }

    public function test_stale_or_conflicting_flowtrack_job_cannot_replace_progress(): void
    {
        $order = $this->order();
        $url = route('api.flowtrack.orders.progress', ['orderNumber' => $order->order_number]);
        $data = [
            'source_order_id' => $order->id, 'flow_job_id' => 16,
            'status' => 'in_progress', 'stage' => 'Artwork', 'progress' => 35,
            'changed_at' => now()->subMinute()->utc()->format('Y-m-d\TH:i:sP'),
        ];
        $this->withToken('only-for-server-tests')->postJson($url, $data)->assertOk();
        $this->withToken('only-for-server-tests')->postJson($url, array_merge($data, [
            'changed_at' => now()->subMinutes(4)->utc()->format('Y-m-d\TH:i:sP'),
            'stage' => 'Outdated',
        ]))->assertOk()->assertJsonPath('applied', false);
        $this->withToken('only-for-server-tests')->postJson($url, array_merge($data, [
            'flow_job_id' => 17, 'changed_at' => now()->utc()->format('Y-m-d\TH:i:sP'),
        ]))->assertStatus(409);
        $this->assertSame('Artwork', $order->fresh()->flowtrack_operational_stage);
    }

    public function test_microsecond_callbacks_within_same_second_are_not_lost(): void
    {
        $order = $this->order();
        $url = route('api.flowtrack.orders.progress', ['orderNumber' => $order->order_number]);
        $second = now()->subMinute()->utc()->format('Y-m-d\TH:i:s');
        $payload = [
            'source_order_id' => $order->id,
            'flow_job_id' => 333,
            'status' => 'in_progress',
            'stage' => 'Artwork',
            'progress' => 17,
            'changed_at' => $second.'.100000+00:00',
        ];
        $this->withToken('only-for-server-tests')->postJson($url, $payload)
            ->assertOk()->assertJsonPath('applied', true);
        $this->withToken('only-for-server-tests')->postJson($url, array_merge($payload, [
            'stage' => 'Production', 'progress' => 55,
            'changed_at' => $second.'.900000+00:00',
        ]))->assertOk()->assertJsonPath('applied', true);
        $this->withToken('only-for-server-tests')->postJson($url, $payload)
            ->assertOk()->assertJsonPath('applied', false);
        $order->refresh();
        $this->assertSame('Production', $order->flowtrack_operational_stage);
        $this->assertSame(55, $order->flowtrack_operational_progress);
        $this->assertSame('pending', $order->payment_status);
    }

}
