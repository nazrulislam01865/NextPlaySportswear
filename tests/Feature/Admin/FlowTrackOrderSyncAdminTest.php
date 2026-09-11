<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use App\Services\Integrations\FlowTrack\FlowTrackOrderSyncManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlowTrackOrderSyncAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_retry_persists_successful_flowtrack_order_sync_state(): void
    {
        $this->enableSynchronousFlowTrack();
        Http::fake([
            'https://flowtrack.example.test/*' => Http::response([
                'ok' => true,
                'message' => 'Order accepted.',
                'data' => [
                    'order_id' => 8001,
                    'order_number' => 'FT-ORDER-8001',
                    'flow_job_id' => 'JOB-8001',
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $order = $this->order();
        $originalUpdatedAt = $order->updated_at?->copy();

        $this->travel(1)->minute();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.orders.retry-sync', $order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $order->refresh();

        $this->assertSame(FlowTrackOrderSyncManager::STATUS_SYNCED, $order->flowtrack_sync_status);
        $this->assertSame(1, $order->flowtrack_sync_attempts);
        $this->assertSame(8001, $order->flowtrack_order_id);
        $this->assertSame('FT-ORDER-8001', $order->flowtrack_order_number);
        $this->assertSame('JOB-8001', $order->flowtrack_job_id);
        $this->assertNull($order->flowtrack_sync_error);
        $this->assertNotNull($order->flowtrack_synced_at);
        $this->assertTrue($originalUpdatedAt?->equalTo($order->updated_at) ?? false);
    }

    public function test_failed_retry_is_persisted_without_changing_the_order_business_state(): void
    {
        $this->enableSynchronousFlowTrack();
        Http::fake([
            'https://flowtrack.example.test/*' => Http::response([
                'ok' => false,
                'message' => 'No active complete Order workflow.',
            ], 422),
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $order = $this->order(['status' => 'payment_review']);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.retry-sync', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('flowtrack');

        $order->refresh();

        $this->assertSame('payment_review', $order->status);
        $this->assertSame(FlowTrackOrderSyncManager::STATUS_FAILED, $order->flowtrack_sync_status);
        $this->assertSame(1, $order->flowtrack_sync_attempts);
        $this->assertStringContainsString('No active complete Order workflow', (string) $order->flowtrack_sync_error);
    }

    /** @param array<string,mixed> $overrides */
    private function order(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'order_number' => 'NP-'.strtoupper(fake()->unique()->bothify('######')),
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'Order Customer',
            'customer_email' => fake()->unique()->safeEmail(),
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 10,
            'tax_total' => 0,
            'grand_total' => 110,
            'total_quantity' => 1,
            'placed_at' => now(),
            'flowtrack_sync_status' => FlowTrackOrderSyncManager::STATUS_PENDING,
        ], $overrides));
    }

    private function enableSynchronousFlowTrack(): void
    {
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.base_url', 'https://flowtrack.example.test');
        config()->set('flowtrack.token', 'test-token');
        config()->set('flowtrack.endpoints.orders', '/api/integrations/nextplay/orders');
        config()->set('flowtrack.queue.enabled', false);
    }
}
