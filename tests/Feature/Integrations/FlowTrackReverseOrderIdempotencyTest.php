<?php

namespace Tests\Feature\Integrations;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FlowTrackReverseOrderIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_replays_return_the_same_order_only_for_the_original_customer(): void
    {
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'only-for-server-tests');
        $buyer = User::factory()->create();
        $buyer->forceFill(['role' => 'customer', 'is_active' => true])->save();
        $other = User::factory()->create();
        $other->forceFill(['role' => 'customer', 'is_active' => true])->save();
        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'order_number' => 'NP-REVERSE-IDEMPOTENT',
            'status' => 'quote_invoice_requested',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'subtotal' => 30,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 30,
            'total_quantity' => 1,
            'idempotency_key' => 'flowtrack:7820',
            'flowtrack_job_id' => '7820',
            'placed_at' => now(),
        ]);
        $payload = [
            'flow_job_id' => 7820,
            'customer_id' => $buyer->id,
            'customer_email' => $buyer->email,
            'shipping_address' => ['address' => '1 Test Street'],
            'items' => [['product_id' => 42, 'quantity' => 1]],
        ];
        $url = '/api/integrations/flowtrack/orders/from-flowtrack';
        $this->withToken('only-for-server-tests')->postJson($url, $payload)
            ->assertOk()->assertJsonPath('replayed', true)
            ->assertJsonPath('order.id', $order->id);
        $this->withToken('only-for-server-tests')->postJson($url, array_merge($payload, [
            'customer_id' => $other->id, 'customer_email' => $other->email,
        ]))->assertStatus(409);
        $this->assertSame(1, Order::query()->where('idempotency_key', 'flowtrack:7820')->count());
    }
}
