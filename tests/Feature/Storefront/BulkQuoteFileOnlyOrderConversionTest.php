<?php

namespace Tests\Feature\Storefront;

use App\Jobs\Integrations\SyncOrderToFlowTrack;
use App\Models\BulkQuoteRequest;
use App\Models\User;
use App\Services\Integrations\FlowTrack\FlowTrackOrderPayloadFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class BulkQuoteFileOnlyOrderConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('transactional_email.enabled', false);
        config()->set('transactional_email.recipients.sales', '');
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.queue.enabled', true);
        Queue::fake();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
    }

    private function fileQuote(User $customer, bool $registered = true, array $overrides = []): BulkQuoteRequest
    {
        return BulkQuoteRequest::create(array_merge([
            'user_id' => $registered ? $customer->id : null,
            'reference' => 'BQ-FILE-'.strtoupper(fake()->unique()->bothify('????####')),
            'full_name' => $customer->name,
            'email' => $customer->email,
            'phone' => '+1 555 0123',
            'product_type' => 'Team Jerseys',
            'estimated_quantity' => '10-49',
            'shipping_address' => '123 Test Lane',
            'country' => 'United States',
            'status' => BulkQuoteRequest::STATUS_QUOTATION_SENT,
            'quotation_version' => 1,
            'quotation_sent_at' => now(),
            'quote_currency' => 'USD',
            'quoted_amount' => null,
            'flowtrack_inquiry_id' => 165,
            'quotation_details' => ['pricing_pending' => true, '_publication_origin' => 'flowtrack'],
            'product_information' => [
                'product_name' => 'Basketball Jersey',
                'sku' => 'NPS-BSK-JER-UR-006',
                'size_groups' => [['label' => 'Youth', 'sizes' => [
                    ['size' => 'YS', 'quantity' => 5], ['size' => 'YL', 'quantity' => 5],
                ]]],
            ],
        ], $overrides));
    }

    public function test_customer_can_convert_file_only_quote_once_and_order_sync_is_queued(): void
    {
        $customer = $this->customer();
        $quote = $this->fileQuote($customer);
        $this->actingAs($customer, 'web');
        $this->get(route('account.bulk-quotes.show', $quote->reference))
            ->assertOk()->assertSee('Confirm &amp; Create Order', false)
            ->assertSee('pending-invoice order');

        $url = route('account.bulk-quotes.decision', $quote->reference);
        $this->post($url, ['decision' => 'accept_only'])->assertRedirect();
        $this->assertNull($quote->fresh()->converted_order_id);
        $this->post($url, ['decision' => 'create_order'])->assertRedirect();

        $quote->refresh();
        $this->assertSame(BulkQuoteRequest::STATUS_CONVERTED, $quote->status);
        $order = $quote->convertedOrder;
        $this->assertNotNull($order);
        $this->assertTrue($order->awaitingQuotePrice());
        $this->assertSame(10, $order->total_quantity);
        $this->assertEquals(0, $order->grand_total);
        $this->assertSame('quote_invoice_requested', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertFalse($order->canPay());
        $this->assertSame('NPS-BSK-JER-UR-006', $order->items()->firstOrFail()->sku);
        $this->assertSame(165, data_get($order->information, 'flowtrack_inquiry_id'));
        $this->assertSame($quote->reference, data_get($order->information, 'reference'));
        Queue::assertPushed(SyncOrderToFlowTrack::class);

        $payload = app(FlowTrackOrderPayloadFactory::class)->make($order);
        $this->assertSame(10, $payload['total_quantity']);
        $this->assertSame(165, data_get($payload, 'information.flowtrack_inquiry_id'));
        $this->assertSame('NPS-BSK-JER-UR-006', $payload['items'][0]['sku']);
        $this->assertCount(2, $payload['items'][0]['sizes']);
        $this->assertSame(5, $payload['items'][0]['sizes'][0]['quantity']);

        $this->post($url, ['decision' => 'create_order'])->assertRedirect();
        $this->assertSame($order->id, $quote->fresh()->converted_order_id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->get(route('account.orders.show', $order))->assertOk()->assertSee('Pending invoice');
        $this->get(route('account.orders.invoice', $order))->assertStatus(409);
    }

    public function test_guest_link_can_convert_after_matching_customer_login_and_exact_quantity_entry(): void
    {
        $customer = $this->customer();
        $quote = $this->fileQuote($customer, false, [
            'product_information' => ['product_name' => 'Team Apparel'],
        ]);
        $this->post(route('quote.guest.lookup'), [
            'reference' => $quote->reference, 'email' => $quote->email,
        ])->assertRedirect();
        $this->get(route('quote.guest.review', $quote->reference))
            ->assertOk()->assertSee('Create Account to Order');
        $this->actingAs($customer, 'web');
        $this->get(route('quote.guest.review', $quote->reference))
            ->assertOk()->assertSee('Exact order quantity');
        $this->post(route('quote.guest.decision', $quote->reference), [
            'decision' => 'create_order',
        ])->assertSessionHasErrors('order_quantity');
        $this->assertDatabaseCount('orders', 0);

        $this->post(route('quote.guest.decision', $quote->reference), [
            'decision' => 'create_order', 'order_quantity' => 24,
        ])->assertRedirect(route('quote.guest.review', $quote->reference));
        $this->assertSame(24, $quote->fresh()->convertedOrder->total_quantity);
        $this->assertSame($customer->id, $quote->fresh()->user_id);
    }

    public function test_incomplete_or_expired_file_quote_cannot_create_an_order(): void
    {
        $customer = $this->customer();
        $quote = $this->fileQuote($customer, true, ['quotation_valid_until' => now()->subDay()->toDateString()]);
        $this->actingAs($customer, 'web')
            ->post(route('account.bulk-quotes.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertSessionHasErrors('quote');
        $this->assertDatabaseCount('orders', 0);
    }
}
