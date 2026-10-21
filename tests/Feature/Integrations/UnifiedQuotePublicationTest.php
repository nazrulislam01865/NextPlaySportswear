<?php

namespace Tests\Feature\Integrations;

use App\Models\BulkQuoteRequest;
use App\Services\BulkQuote\UnifiedQuotePublisher;
use App\Contracts\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class UnifiedQuotePublicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('safelyQueue')->zeroOrMoreTimes()->andReturnTrue();
        $this->app->instance(EmailService::class, $email);
    }

    private function quote(): BulkQuoteRequest
    {
        return BulkQuoteRequest::create([
            'reference' => 'BQ-UNIFIED-TEST', 'full_name' => 'Sample Customer',
            'organization' => 'Sample Team', 'email' => 'client@example.test',
            'phone' => '1234567890', 'product_type' => 'Jerseys',
            'estimated_quantity' => '10-49', 'sizes_needed' => 'S, M',
            'artwork_details' => 'Test lettering', 'shipping_address' => '123 Test St',
            'country' => 'United States', 'needed_by' => now()->addDays(14)->toDateString(),
            'flowtrack_inquiry_id' => 159,
        ]);
    }

    public function test_completed_offer_publishes_once_and_does_not_fabricate_a_price(): void
    {
        $quote = $this->quote();
        $hash = str_repeat('c', 64);
        Storage::disk('local')->put('private/approved.pdf', '%PDF-1.4 test');
        $quote->quoteFiles()->create([
            'category' => 'flowtrack_offer', 'version' => 0,
            'path' => 'private/approved.pdf', 'original_name' => 'approved.pdf',
            'mime_type' => 'application/pdf', 'size' => 13,
            'source_key' => 'flowtrack-offer:159:649:25',
        ]);
        $quote->forceFill([
            'flowtrack_offer_manifest_hash' => $hash,
            'flowtrack_offer_ready_hash' => $hash,
            'flowtrack_offer_file_count' => 1,
        ])->saveQuietly();
        $publisher = app(UnifiedQuotePublisher::class);
        $this->assertTrue($publisher->fromFlowTrack($quote->id, $hash));
        $this->assertFalse($publisher->fromFlowTrack($quote->id, $hash));
        $quote->refresh();
        $this->assertSame(1, $quote->quotation_version);
        $this->assertNotNull($quote->quotation_sent_at);
        $this->assertNull($quote->quoted_amount);
        $this->assertTrue($quote->quotation_details['pricing_pending']);
        $this->assertSame(1, $quote->quoteFiles()->where('category', 'quotation')->count());
    }

    public function test_unverified_offer_cannot_publish(): void
    {
        $quote = $this->quote();
        $quote->forceFill(['flowtrack_offer_manifest_hash' => str_repeat('b', 64)])->saveQuietly();
        $this->assertFalse(app(UnifiedQuotePublisher::class)->fromFlowTrack($quote->id, str_repeat('b', 64)));
        $this->assertSame(0, $quote->fresh()->quotation_version);
    }

    public function test_admin_publication_file_endpoint_requires_server_token(): void
    {
        $quote = $this->quote();
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'test-private-token');
        $file = $quote->quoteFiles()->create([
            'category' => 'quotation', 'version' => 1, 'path' => 'private/a.pdf',
            'original_name' => 'a.pdf', 'mime_type' => 'application/pdf', 'size' => 4,
        ]);
        Storage::disk('local')->put('private/a.pdf', 'test');
        $url = "/api/integrations/flowtrack/bulk-quotes/{$quote->id}/published-files/{$file->id}";
        $this->get($url)->assertUnauthorized();
        $this->withToken('test-private-token')->get($url)->assertNotFound();
        $quote->forceFill(['quotation_version' => 1, 'quotation_sent_at' => now()])->saveQuietly();
        $this->withToken('test-private-token')->get($url)->assertOk();
    }
}
