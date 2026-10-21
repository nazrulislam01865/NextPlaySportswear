<?php

namespace Tests\Feature\Integrations;

use App\Contracts\EmailService;
use App\Models\BulkQuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class VerifiedFlowTrackOfferRecoveryTest extends TestCase
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

    private function stagedOffer(): BulkQuoteRequest
    {
        $quote = BulkQuoteRequest::query()->create([
            'reference' => 'BQ-RECOVERY-TEST', 'full_name' => 'Buyer',
            'organization' => 'Team', 'email' => 'buyer@example.test',
            'phone' => '+12025550123', 'product_type' => 'Jerseys',
            'estimated_quantity' => '10-49', 'sizes_needed' => 'S, M',
            'artwork_details' => 'Team uniform', 'shipping_address' => '123 Example St',
            'country' => 'United States', 'needed_by' => now()->addWeeks(2)->toDateString(),
            'flowtrack_inquiry_id' => 159,
        ]);
        $contents = '%PDF-1.4 sample';
        Storage::disk('local')->put('bulk-quote-private/verified-offer.pdf', $contents);
        $quote->quoteFiles()->create([
            'category' => 'flowtrack_offer', 'version' => 0,
            'path' => 'bulk-quote-private/verified-offer.pdf',
            'original_name' => 'verified-offer.pdf', 'mime_type' => 'application/pdf',
            'size' => strlen($contents), 'source_key' => 'flowtrack-offer:159:649:101',
        ]);
        $hash = str_repeat('a', 64);
        $quote->forceFill([
            'flowtrack_offer_manifest_hash' => $hash,
            'flowtrack_offer_ready_hash' => $hash,
            'flowtrack_offer_file_count' => 1,
        ])->saveQuietly();

        return $quote;
    }

    public function test_recovery_publishes_existing_verified_offer_and_restores_review_actions(): void
    {
        $quote = $this->stagedOffer();
        $this->withSession(['guest_bulk_quotes.'.$quote->id => true])
            ->get(route('quote.guest.show', $quote->reference))
            ->assertOk()->assertSee('Offer File Available')->assertDontSee('Review Quotation');

        $this->artisan('flowtrack:publish-verified-offer', ['quote' => (string) $quote->id, '--check' => true])
            ->assertExitCode(0);
        $this->assertSame(0, $quote->fresh()->quotation_version);

        $this->artisan('flowtrack:publish-verified-offer', ['quote' => $quote->reference])
            ->assertExitCode(0);
        $this->artisan('flowtrack:publish-verified-offer', ['quote' => $quote->reference])
            ->assertExitCode(0);

        $quote->refresh();
        $this->assertSame(1, $quote->quotation_version);
        $this->assertNotNull($quote->quotation_sent_at);
        $this->assertNull($quote->quoted_amount);
        $this->assertSame(1, $quote->quoteFiles()->where('category', 'quotation')->count());

        $this->withSession(['guest_bulk_quotes.'.$quote->id => true])
            ->get(route('quote.guest.show', $quote->reference))
            ->assertOk()->assertSee('Review Quotation')->assertSee('Send Feedback');
        $this->withSession(['guest_bulk_quotes.'.$quote->id => true])
            ->get(route('quote.guest.review', $quote->reference))
            ->assertOk()->assertSee('Request Changes')->assertSee('Confirm Quotation')
            ->assertDontSee('Confirm & Create Order');
    }

    public function test_missing_private_file_is_not_published(): void
    {
        $quote = $this->stagedOffer();
        Storage::disk('local')->delete('bulk-quote-private/verified-offer.pdf');

        $this->artisan('flowtrack:publish-verified-offer', ['quote' => (string) $quote->id])
            ->assertExitCode(1);
        $this->assertSame(0, $quote->fresh()->quotation_version);
    }
}
