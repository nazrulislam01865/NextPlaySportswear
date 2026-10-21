<?php

namespace Tests\Feature\Integrations;

use App\Models\BulkQuoteRequest;
use App\Support\FlowTrackQuoteCustomerProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class FlowTrackTaskProgressVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'private-integration-test-token');
        Storage::fake('local');
    }

    private function quote(): BulkQuoteRequest
    {
        return BulkQuoteRequest::query()->create([
            'reference' => 'BQ-TASK-STAGES', 'full_name' => 'Team Buyer', 'organization' => 'Test Club',
            'email' => 'buyer@example.test', 'phone' => '+12025550123', 'product_type' => 'Jerseys',
            'estimated_quantity' => '10-49', 'sizes_needed' => 'M, L',
            'artwork_details' => 'Team jersey artwork', 'shipping_address' => '123 Example Avenue',
            'country' => 'United States', 'needed_by' => now()->addMonth()->toDateString(),
            'flowtrack_inquiry_id' => 159,
        ]);
    }

    public function test_task_progress_requires_authentication_and_exact_inquiry_ownership(): void
    {
        $quote = $this->quote();
        $url = '/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/task-progress';
        $payload = ['flowtrack_inquiry_id' => 159, 'review_at' => now()->toIso8601String()];
        $this->postJson($url, $payload)->assertUnauthorized();
        $this->withToken('private-integration-test-token')->postJson($url,
            ['flowtrack_inquiry_id' => 160, 'review_at' => now()->toIso8601String()])->assertNotFound();
        $this->assertNull($quote->fresh()->flowtrack_task_progress);
    }

    public function test_task_progress_updates_customer_timeline_without_publishing_a_priced_quotation(): void
    {
        $quote = $this->quote();
        $now = now()->toIso8601String();
        $this->withToken('private-integration-test-token')->postJson(
            '/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/task-progress',
            ['flowtrack_inquiry_id' => 159, 'review_at' => $now, 'prepared_at' => $now]
        )->assertOk();

        $quote->refresh();
        $this->assertSame('Quotation Prepared', FlowTrackQuoteCustomerProgress::title($quote));
        $this->assertSame(BulkQuoteRequest::STATUS_NEW, $quote->status);
        $this->assertNull($quote->quotation_sent_at);
        $this->assertFalse(FlowTrackQuoteCustomerProgress::verifiedOffer($quote));
    }

    public function test_only_a_complete_verified_offer_is_customer_downloadable(): void
    {
        $quote = $this->quote();
        $file = $quote->quoteFiles()->create([
            'source_key' => 'flowtrack-offer:159:649:81', 'category' => 'flowtrack_offer',
            'version' => 0, 'path' => 'bulk-quote-private/test-offer.pdf',
            'original_name' => 'test-offer.pdf', 'mime_type' => 'application/pdf', 'size' => 8,
        ]);
        Storage::disk('local')->put($file->path, '%PDF-1.4');
        $manifest = str_repeat('c', 64);
        $url = route('quote.guest.file', [$quote->reference, $file]);
        $session = ['guest_bulk_quotes.'.$quote->id => true];

        $this->withSession($session)->get($url)->assertNotFound();
        $quote->forceFill([
            'flowtrack_offer_manifest_hash' => $manifest,
            'flowtrack_offer_ready_hash' => $manifest,
            'flowtrack_offer_file_count' => 1,
        ])->saveQuietly();
        $this->assertTrue(FlowTrackQuoteCustomerProgress::verifiedOffer($quote->fresh()));
        $this->withSession($session)->get($url)->assertOk();
        $this->withSession($session)->get(route('quote.guest.show', $quote->reference))
            ->assertOk()->assertSee('test-offer.pdf')->assertSee('Offer File Available');

        // A reopened task invalidates the readiness marker immediately.
        $quote->forceFill(['flowtrack_offer_ready_hash' => null, 'flowtrack_offer_file_count' => 0])->saveQuietly();
        $this->withSession($session)->get($url)->assertNotFound();
    }
}
