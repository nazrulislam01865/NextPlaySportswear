<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Integrations\TransferFlowTrackBulkQuoteFiles;
use App\Models\BulkQuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class FlowTrackSelectedBulkQuoteProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'private-quote-sync-test-token');
        Queue::fake();
    }

    private function quote(): BulkQuoteRequest
    {
        return BulkQuoteRequest::query()->create([
            'reference' => 'BQ-SYNC-CHOICES', 'full_name' => 'Buyer Name',
            'organization' => 'Test Team', 'email' => 'buyer@example.test',
            'phone' => '+12025550123', 'product_type' => 'Jerseys',
            'estimated_quantity' => '50-99', 'sizes_needed' => 'M, L',
            'artwork_details' => 'Blue jerseys with team crest',
            'shipping_address' => '24 Sports Road, Central District',
            'country' => 'Bangladesh', 'needed_by' => '2026-12-01',
            'flowtrack_inquiry_id' => 313,
            'product_information' => [
                'product_id' => 15, 'product_name' => 'Original jersey', 'sku' => 'NPS-JERSEY',
                'options' => [['label' => 'Fabric', 'value' => 'Reversible']],
            ],
        ]);
    }

    private function payload(BulkQuoteRequest $quote): array
    {
        return [
            'source' => 'nextplay', 'flowtrack_inquiry_id' => 313,
            'source_quote_id' => $quote->id, 'source_reference' => $quote->reference,
            'full_name' => $quote->full_name, 'organization' => $quote->organization,
            'email' => $quote->email, 'phone' => $quote->phone,
            'product_type' => $quote->product_type, 'estimated_quantity' => $quote->estimated_quantity,
            'sizes_needed' => $quote->sizes_needed, 'artwork_details' => $quote->artwork_details,
            'shipping_address' => $quote->shipping_address, 'country' => $quote->country,
            'needed_by' => $quote->needed_by->toDateString(), 'status' => $quote->status,
            'selected_products' => [[
                'id' => 80, 'name' => 'Confirmed jersey', 'category' => 'Apparel',
                'quantity' => 30, 'unit' => 'pcs',
            ]],
        ];
    }

    public function test_flowtracker_items_sync_without_destroying_original_picker_selection(): void
    {
        $quote = $this->quote();
        $payload = $this->payload($quote);

        $this->withToken('private-quote-sync-test-token')
            ->postJson('/api/integrations/flowtrack/bulk-orders', $payload)
            ->assertOk()->assertJsonPath('quote_id', $quote->id);

        $info = $quote->fresh()->product_information;
        $this->assertSame('Original jersey', $info['product_name']);
        $this->assertSame('Reversible', $info['options'][0]['value']);
        $this->assertSame($payload['selected_products'], $info['selected_products']);
        Queue::assertPushed(TransferFlowTrackBulkQuoteFiles::class);

        $payload['selected_products'] = [];
        $this->withToken('private-quote-sync-test-token')
            ->postJson('/api/integrations/flowtrack/bulk-orders', $payload)->assertOk();
        $info = $quote->fresh()->product_information;
        $this->assertSame('Original jersey', $info['product_name']);
        $this->assertArrayNotHasKey('selected_products', $info);
    }

    public function test_wrong_original_reference_cannot_update_linked_quote(): void
    {
        $quote = $this->quote();
        $payload = $this->payload($quote);
        $payload['source_reference'] = 'BQ-SOMEONE-ELSE';
        $this->withToken('private-quote-sync-test-token')
            ->postJson('/api/integrations/flowtrack/bulk-orders', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('source_reference');
        $this->assertArrayNotHasKey('selected_products', $quote->fresh()->product_information);
        Queue::assertNothingPushed();
    }
}
