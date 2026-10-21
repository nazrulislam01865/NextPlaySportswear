<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Integrations\TransferFlowTrackQuoteOffer;
use App\Models\BulkQuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Contracts\EmailService;
use Mockery;
use Tests\TestCase;

final class FlowTrackQuotationOfferSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.token', 'test-server-private-key');
        Queue::fake();
    }

    private function quote(): BulkQuoteRequest
    {
        return BulkQuoteRequest::query()->create([
            'reference' => 'BQ-OFFER-TEST',
            'full_name' => 'Test Buyer', 'organization' => 'Test Club',
            'email' => 'buyer@example.test', 'phone' => '+12025550123',
            'product_type' => 'Jerseys', 'estimated_quantity' => '10-49',
            'sizes_needed' => 'M, L', 'artwork_details' => 'Blue jerseys with logo',
            'shipping_address' => '123 Sample Street', 'country' => 'United States',
            'needed_by' => now()->addMonth()->toDateString(),
            'flowtrack_inquiry_id' => 918,
        ]);
    }

    private function manifest(): array
    {
        return [
            'flowtrack_inquiry_id' => 918,
            'flowtrack_task_id' => 303,
            'completed' => true,
            'files' => [[
                'id' => 41, 'name' => 'approved-offer.pdf', 'size' => 15,
                'mime_type' => 'application/pdf', 'checksum_sha256' => str_repeat('a', 64),
                'download_path' => '/api/integrations/nextplay/bulk-orders/918/offer-tasks/303/files/41',
            ]],
        ];
    }

    public function test_offer_endpoint_rejects_missing_credentials(): void
    {
        $quote = $this->quote();
        $this->postJson('/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/offer-files', $this->manifest())
            ->assertUnauthorized();
        Queue::assertNothingPushed();
    }

    public function test_offer_requires_exact_quote_to_inquiry_binding(): void
    {
        $quote = $this->quote();
        $payload = $this->manifest();
        $payload['flowtrack_inquiry_id'] = 999;
        $this->withToken('test-server-private-key')
            ->postJson('/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/offer-files', $payload)
            ->assertStatus(422); // Incorrect private file path is rejected before any writes.
        Queue::assertNothingPushed();

        $payload = $this->manifest();
        $payload['flowtrack_inquiry_id'] = 999;
        $payload['files'][0]['download_path'] = '/api/integrations/nextplay/bulk-orders/999/offer-tasks/303/files/41';
        $this->withToken('test-server-private-key')
            ->postJson('/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/offer-files', $payload)
            ->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_completed_offer_queues_the_same_formal_publication_without_premature_visibility(): void
    {
        $quote = $this->quote();
        $this->withToken('test-server-private-key')
            ->postJson('/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/offer-files', $this->manifest())
            ->assertOk()->assertJsonPath('queued_files', 1);
        Queue::assertPushed(TransferFlowTrackQuoteOffer::class, 1);
        $quote->refresh();
        $this->assertSame(BulkQuoteRequest::STATUS_NEW, $quote->status);
        $this->assertNull($quote->quotation_sent_at);
        $this->assertSame(0, (int) $quote->quotation_version);
        $this->assertNotEmpty($quote->flowtrack_offer_manifest_hash);
    }

    public function test_identical_manifest_with_missing_private_file_revokes_stale_readiness_and_retries(): void
    {
        Storage::fake('local');
        $quote = $this->quote();
        $payload = $this->manifest();
        $this->withToken('test-server-private-key')
            ->postJson('/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/offer-files', $payload)
            ->assertOk();
        $hash = (string) $quote->fresh()->flowtrack_offer_manifest_hash;
        $quote->quoteFiles()->create([
            'category' => 'flowtrack_offer', 'version' => 0,
            'path' => 'bulk-quote-private/missing.pdf', 'original_name' => 'approved-offer.pdf',
            'mime_type' => 'application/pdf', 'size' => 15,
            'source_key' => 'flowtrack-offer:918:303:41',
        ]);
        $quote->forceFill(['flowtrack_offer_ready_hash' => $hash])->saveQuietly();

        $this->withToken('test-server-private-key')
            ->postJson('/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/offer-files', $payload)
            ->assertOk();
        $this->assertNull($quote->fresh()->flowtrack_offer_ready_hash);
        Queue::assertPushed(TransferFlowTrackQuoteOffer::class, 2);
    }

    public function test_completed_offer_publishes_and_enables_review_without_a_second_admin_action(): void
    {
        Storage::fake('local');
        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('safelyQueue')->zeroOrMoreTimes()->andReturnTrue();
        $this->app->instance(EmailService::class, $email);
        config()->set('flowtrack.base_url', 'http://127.0.0.1:8001');

        $quote = $this->quote();
        $content = '%PDF-1.4 hello';
        $file = $this->manifest()['files'][0];
        $file['size'] = strlen($content);
        $file['checksum_sha256'] = hash('sha256', $content);
        $manifest = hash('sha256', json_encode([303, true, [$file]], JSON_THROW_ON_ERROR));
        $storedPath = 'bulk-quote-private/'.$quote->id.'/flowtrack-offers/offer.pdf';
        Storage::disk('local')->put($storedPath, $content);
        $quote->quoteFiles()->create([
            'category' => 'flowtrack_offer', 'version' => 0,
            'path' => $storedPath, 'original_name' => $file['name'],
            'mime_type' => $file['mime_type'], 'size' => strlen($content),
            'source_key' => 'flowtrack-offer:918:303:41',
        ]);
        $quote->forceFill([
            'flowtrack_offer_manifest_hash' => $manifest,
            'flowtrack_offer_file_count' => 1,
        ])->saveQuietly();

        $job = new TransferFlowTrackQuoteOffer($quote->id, 918, 303, [$file], $manifest);
        $job->handle();
        $quote->refresh();
        $this->assertSame(1, (int) $quote->quotation_version);
        $this->assertNotNull($quote->quotation_sent_at);
        $this->assertNull($quote->quoted_amount);
        $this->assertSame('quotation_sent', $quote->status);
        $this->assertSame(1, $quote->quoteFiles()->where('category', 'quotation')->count());

        $job->handle();
        $this->assertSame(1, (int) $quote->fresh()->quotation_version);
        $this->assertSame(1, $quote->quoteFiles()->where('category', 'quotation')->count());
        $this->withSession(['guest_bulk_quotes.'.$quote->id => true])
            ->get(route('quote.guest.show', $quote->reference))
            ->assertOk()->assertSee('Review Quotation')->assertSee('Send Feedback');
    }

    public function test_task_reopening_queues_removal_of_unpublished_staging(): void
    {
        $quote = $this->quote();
        $payload = $this->manifest();
        $payload['completed'] = false;
        $payload['files'] = [];
        $this->withToken('test-server-private-key')
            ->postJson('/api/integrations/flowtrack/bulk-quotes/'.$quote->id.'/offer-files', $payload)
            ->assertOk()->assertJsonPath('queued_files', 0);
        Queue::assertPushed(TransferFlowTrackQuoteOffer::class, 1);
    }
}
