<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackOrderLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_page_does_not_render_demo_result_before_verification(): void
    {
        $this->get(route('orders.track'))
            ->assertOk()
            ->assertSee('Look Up Your Order')
            ->assertDontSee('Example Tracking Result')
            ->assertDontSee('Tracking Result');
    }

    public function test_order_number_and_checkout_email_show_current_database_tracking_result(): void
    {
        $order = $this->createTrackableOrder('NP-260929-ABC123', 'proof_approval');

        $order->histories()->create([
            'status' => 'proof_approval',
            'title' => 'Proof Approval',
            'description' => 'Proof is ready for approval.',
            'occurred_at' => now(),
        ]);

        $response = $this->post(route('orders.track.lookup'), [
            'order_number' => 'np-260929-abc123',
            'email' => 'buyer@example.com',
        ]);

        $response->assertRedirect(route('orders.track'));

        $this->get(route('orders.track'))
            ->assertOk()
            ->assertSee('Tracking Result')
            ->assertSee('NP-260929-ABC123')
            ->assertSee('Proof Approval')
            ->assertSee('Payment Confirmed')
            ->assertSee('Custom Basketball Jersey')
            ->assertDontSee('Example Tracking Result');

        $this->get(route('orders.details', ['orderNumber' => $order->order_number]))
            ->assertOk()
            ->assertSee($order->order_number);

        $order->update(['status' => 'in_production']);

        $this->get(route('orders.track'))
            ->assertOk()
            ->assertSee('Status: In Production');
    }

    public function test_shipment_tracking_number_and_email_can_find_the_order_and_show_tracking_details(): void
    {
        $order = $this->createTrackableOrder('NP-260929-SHIP01', 'shipped');

        $order->shipments()->create([
            'shipment_number' => 'SHP-260929-0001',
            'status' => 'in_transit',
            'carrier' => 'UPS',
            'service' => 'Ground',
            'tracking_number' => '1Z999AA10123456784',
            'tracking_url' => 'https://example.com/track/1Z999AA10123456784',
            'shipped_at' => now(),
        ]);

        $this->post(route('orders.track.lookup'), [
            'order_number' => '1Z999AA10123456784',
            'email' => 'buyer@example.com',
        ])->assertRedirect(route('orders.track'));

        $this->get(route('orders.track'))
            ->assertOk()
            ->assertSee('Tracking number: 1Z999AA10123456784')
            ->assertSee('UPS')
            ->assertSee('Track shipment');
    }

    public function test_wrong_email_does_not_expose_an_order(): void
    {
        $this->createTrackableOrder('NP-260929-PRIVATE', 'in_production');

        $this->from(route('orders.track'))->post(route('orders.track.lookup'), [
            'order_number' => 'NP-260929-PRIVATE',
            'email' => 'someone-else@example.com',
        ])
            ->assertRedirect(route('orders.track'))
            ->assertSessionHasErrors('order_number');

        $this->get(route('orders.track'))
            ->assertOk()
            ->assertDontSee('NP-260929-PRIVATE')
            ->assertDontSee('Tracking Result');
    }

    private function createTrackableOrder(string $orderNumber, string $status): Order
    {
        $order = Order::query()->create([
            'order_number' => $orderNumber,
            'status' => $status,
            'payment_status' => 'paid',
            'fulfillment_status' => in_array($status, ['shipped', 'delivered', 'completed'], true) ? 'fulfilled' : 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'Tracking Customer',
            'customer_email' => 'buyer@example.com',
            'subtotal' => 78,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 78,
            'total_quantity' => 2,
            'placed_at' => now()->subDay(),
            'paid_at' => now()->subDay(),
        ]);

        $order->items()->create([
            'product_name' => 'Custom Basketball Jersey',
            'product_slug' => 'custom-basketball-jersey',
            'image_url' => '/images/product-placeholder.svg',
            'quantity' => 2,
            'unit_price' => 39,
            'customization_unit_price' => 0,
            'line_total' => 78,
            'customization' => [
                'size_summary' => 'M x2',
                'design_option' => 'Team Design',
                'notes' => 'Team Name, Number',
            ],
            'is_digital' => false,
        ]);

        return $order;
    }
}
