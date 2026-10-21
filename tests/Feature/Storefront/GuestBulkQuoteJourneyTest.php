<?php

namespace Tests\Feature\Storefront;

use App\Models\BulkQuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestBulkQuoteJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('transactional_email.enabled', false);
        config()->set('transactional_email.recipients.sales', '');
    }

    public function test_lookup_shows_required_and_format_errors_beneath_the_respective_fields(): void
    {
        $track = route('quote.guest.track');
        $this->get($track)->assertOk()
            ->assertSee('data-guest-quote-lookup novalidate', false)
            ->assertSee('id="guest-quote-reference-error"', false)
            ->assertSee('id="guest-quote-email-error"', false);

        $this->from($track)->post(route('quote.guest.lookup'), [
            'reference' => '', 'email' => '',
        ])->assertRedirect($track)->assertSessionHasErrors(['reference', 'email']);

        $this->get($track)->assertOk()
            ->assertSee('The reference field is required.')
            ->assertSee('The email field is required.')
            ->assertSee('aria-describedby="guest-quote-reference-error"', false)
            ->assertSee('aria-describedby="guest-quote-email-error"', false);

        $this->from($track)->post(route('quote.guest.lookup'), [
            'reference' => 'BQ-EXAMPLE', 'email' => 'not-an-email',
        ])->assertRedirect($track)->assertSessionHasErrors('email');
    }

    public function test_private_quote_requires_valid_token_or_reference_and_email(): void
    {
        $quote = $this->quote();
        $this->get(route('quote.guest.show', $quote->reference))->assertNotFound();
        $this->get(route('quote.guest.show', ['reference' => $quote->reference, 'token' => 'invalid']))->assertNotFound();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => 'wrong@example.test'])
            ->assertSessionHasErrors('reference');
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email])
            ->assertRedirect(route('quote.guest.show', $quote->reference));
        $this->get(route('quote.guest.show', $quote->reference))->assertOk()->assertSee('Track Bulk Quote')
            ->assertHeader('cache-control', 'private, no-store, max-age=0');
    }

    public function test_capability_link_opens_quote_and_no_other_quotes(): void
    {
        $quote = $this->quote();
        $other = $this->quote(['reference' => 'BQ-OTHER-123']);
        $token = 'a'.str_repeat('c', 63);
        $quote->forceFill(['guest_access_hash' => hash('sha256', $token)])->save();
        $this->get(route('quote.guest.show', ['reference' => $quote->reference, 'token' => $token]))->assertOk();
        $this->get(route('quote.guest.show', $other->reference))->assertNotFound();
    }

    public function test_admin_can_publish_versioned_files_and_guest_can_review_without_public_file_urls(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $quote = $this->quote();
        $this->actingAs($admin, 'admin')->post(route('admin.bulk-quotes.publish', $quote), [
            'quantity' => 24,
            'subtotal' => '600.00',
            'customization' => '80',
            'shipping' => '30',
            'shipping_method' => 'Express',
            'payment_terms' => 'Invoice before production',
            'valid_until' => now()->addDays(20)->toDateString(),
            'files' => [UploadedFile::fake()->create('pricing.pdf', 60, 'application/pdf')],
        ])->assertRedirect();
        $quote->refresh();
        $this->assertSame(1, $quote->quotation_version);
        $this->assertSame('710.00', $quote->quoted_amount);
        $this->assertDatabaseHas('bulk_quote_files', ['bulk_quote_request_id' => $quote->id, 'version' => 1, 'category' => 'quotation']);
        $file = $quote->quoteFiles()->firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($file->path));
        $this->get(route('quote.guest.file', [$quote->reference, $file]))->assertNotFound();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->get(route('quote.guest.review', $quote->reference))->assertOk()->assertSee('USD 710.00')->assertSee('pricing.pdf');
        $this->get(route('quote.guest.file', [$quote->reference, $file]))->assertOk();
    }

    public function test_guest_can_request_changes_and_admin_can_reply(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->post(route('quote.guest.changes.send', $quote->reference), [
            'change_types' => ['pricing', 'delivery'],
            'body' => 'Please revise the quote for 30 pieces.',
        ])->assertRedirect(route('quote.guest.show', $quote->reference));
        $this->assertSame('customer_responded', $quote->fresh()->status);
        $this->assertDatabaseHas('bulk_quote_messages', ['bulk_quote_request_id' => $quote->id, 'sender' => 'guest', 'kind' => 'change_request']);
        $this->actingAs($admin, 'admin')->post(route('admin.bulk-quotes.message', $quote), ['body' => 'We are working on the revision.'])->assertRedirect();
        $this->get(route('quote.guest.changes', $quote->reference))->assertSee('We are working on the revision.');
    }

    public function test_confirm_only_does_not_create_an_order_and_create_order_is_idempotent(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'accept_only'])
            ->assertRedirect(route('quote.guest.review', $quote->reference))
            ->assertSessionHas('status');
        $this->get(route('quote.guest.review', $quote->reference))->assertOk()->assertSee('Quotation confirmed');
        $this->assertNull($quote->fresh()->converted_order_id);
        $customer = User::factory()->create(['email' => $quote->email, 'role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($customer, 'web');
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('quote.guest.review', $quote->reference))
            ->assertSessionHas('status');
        $orderId = $quote->fresh()->converted_order_id;
        $this->assertNotNull($orderId);
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'quote_invoice_requested', 'grand_total' => 710]);
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('quote.guest.review', $quote->reference))
            ->assertSessionHas('status');
        $this->assertSame($orderId, $quote->fresh()->converted_order_id);
        $this->assertDatabaseCount('order_items', 1);
        $this->get(route('quote.guest.review', $quote->reference))
            ->assertOk()
            ->assertSee($quote->fresh()->convertedOrder->order_number)
            ->assertDontSee('name="decision" value="accept_only"', false);
    }

    public function test_guest_confirmation_only_is_idempotent_and_cannot_be_reversed_into_duplicate_orders(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'accept_only'])
            ->assertRedirect(route('quote.guest.review', $quote->reference));
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'accept_only'])
            ->assertRedirect(route('quote.guest.review', $quote->reference));
        $this->assertDatabaseCount('orders', 0);
        $customer = User::factory()->create(['email' => $quote->email, 'role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($customer, 'web');
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('quote.guest.review', $quote->reference));
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('quote.guest.review', $quote->reference));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('pending', $quote->fresh()->convertedOrder->payment_status);
    }

    public function test_confirmation_allows_normal_retries_but_rate_limits_excessive_replays_without_duplicate_orders(): void
    {
        $quote = $this->publishedQuote();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.170']);
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $customer = User::factory()->create(['email' => $quote->email, 'role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($customer, 'web');

        // The old shared 5/minute IP limiter rejected this normal test workflow.
        // Replaying the accepted request must not create a second order or message.
        for ($attempt = 0; $attempt < 12; $attempt++) {
            $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
                ->assertRedirect(route('quote.guest.review', $quote->reference));
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('bulk_quote_messages', [
            'bulk_quote_request_id' => $quote->id,
            'sender' => 'guest',
            'kind' => 'decision',
        ]);
        $this->assertSame(1, $quote->messages()->where('kind', 'decision')->count());

        // Excessive attempts still receive an actionable message, not Laravel's raw 429 page.
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect()
            ->assertSessionHasErrors('quote');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_review_confirmation_forms_use_existing_single_submit_handler(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->get(route('quote.guest.review', $quote->reference))
            ->assertOk()
            ->assertSee('data-single-submit', false)
            ->assertSee('Create Account to Order');
    }

    public function test_feedback_request_prevents_confirmation_until_admin_publishes_revision(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->post(route('quote.guest.changes.send', $quote->reference), [
            'change_types' => ['pricing'],
            'body' => 'Please adjust the quantity and recalculate the total.',
        ])->assertRedirect();
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'accept_only'])
            ->assertSessionHasErrors('quote');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_registered_quote_cannot_be_opened_as_guest(): void
    {
        $customer = User::factory()->create();
        $quote = $this->quote(['user_id' => $customer->id]);
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email])->assertSessionHasErrors('reference');
    }

    public function test_file_access_cannot_cross_quote_boundary_and_expired_quote_cannot_be_accepted(): void
    {
        $quote = $this->publishedQuote();
        $other = $this->publishedQuote();
        $file = $other->quoteFiles()->create([
            'version' => 1, 'category' => 'quotation', 'path' => 'secret-quote.pdf',
            'original_name' => 'secret-quote.pdf', 'mime_type' => 'application/pdf', 'size' => 30,
        ]);
        Storage::disk('local')->put('secret-quote.pdf', 'secret');
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->get(route('quote.guest.file', [$quote->reference, $file]))->assertNotFound();
        $quote->forceFill(['quotation_valid_until' => now()->subDay()->toDateString()])->save();
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'accept_only'])
            ->assertSessionHasErrors('quote');
        $this->assertNull($quote->fresh()->converted_order_id);
    }

    public function test_guest_message_can_be_sent_repeatedly_without_raw_rate_limit_page_and_is_still_rate_limited(): void
    {
        $quote = $this->publishedQuote();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.241']);
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->post(route('quote.guest.changes.send', $quote->reference), [
            'change_types' => ['pricing'],
            'body' => 'Please revise the pricing in the quotation.',
        ])->assertRedirect();
        $show = route('quote.guest.show', $quote->reference);
        $send = route('quote.guest.message', $quote->reference);

        for ($i = 1; $i <= 24; $i++) {
            $this->from($show)->post($send, ['body' => 'Customer quotation message '.$i])
                ->assertRedirect($show);
        }
        $this->assertSame(24, $quote->messages()->where('kind', 'message')->count());

        // Reaching the limit must not create a message or return the unhelpful 429 screen.
        $this->from($show)->post($send, ['body' => 'An extra message'])
            ->assertRedirect($show)
            ->assertSessionHasErrors('body');
        $this->assertSame(24, $quote->messages()->where('kind', 'message')->count());
        $this->get($show)->assertOk()->assertSee('Please wait a moment before sending another message.');
    }

    public function test_guest_cannot_accept_quote_before_admin_publishes(): void
    {
        $quote = $this->quote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->get(route('quote.guest.review', $quote->reference))->assertNotFound();
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'accept_only'])
            ->assertSessionHasErrors('quote');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_must_create_or_sign_in_to_an_account_before_creating_an_order(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $this->get(route('quote.guest.review', $quote->reference))->assertOk()
            ->assertSee('Create Account to Order')->assertSee('Already registered? Sign in');
        $this->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('register', ['redirect' => route('quote.guest.review', $quote->reference)]));
        $this->assertNull($quote->fresh()->converted_order_id);
        $this->assertDatabaseCount('orders', 0);
        $this->assertNull($quote->fresh()->user_id);
    }

    public function test_matching_customer_account_owns_created_order_and_linked_quote(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $customer = User::factory()->create(['email' => strtoupper($quote->email), 'role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($customer, 'web')->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('quote.guest.review', $quote->reference));
        $quote->refresh();
        $this->assertSame($customer->id, $quote->user_id);
        $this->assertDatabaseHas('orders', ['id' => $quote->converted_order_id, 'user_id' => $customer->id, 'payment_status' => 'pending']);
        $this->get(route('quote.guest.review', $quote->reference))->assertOk()->assertSee('View Order in My Account');
        $this->get(route('account.orders.index'))->assertOk()->assertSee($quote->convertedOrder->order_number);
        $this->get(route('quote.guest.track'))->assertOk();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email])
            ->assertRedirect(route('quote.guest.show', $quote->reference));
        auth('web')->logout();
        $this->get(route('quote.guest.review', $quote->reference))->assertNotFound();
    }

    public function test_account_with_different_email_cannot_claim_or_convert_guest_quote(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $customer = User::factory()->create(['email' => 'different@example.test', 'role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($customer, 'web')->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertSessionHasErrors('quote');
        $this->assertNull($quote->fresh()->user_id);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unverified_account_cannot_convert_quote_when_email_verification_is_required(): void
    {
        config()->set('security.email_verification.enabled', true);
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        $customer = User::factory()->create(['email' => $quote->email, 'role' => 'customer', 'is_active' => true, 'email_verified_at' => null]);
        $this->actingAs($customer, 'web')->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('orders', 0);
        $this->assertNull($quote->fresh()->user_id);
    }

    public function test_legacy_guest_order_can_be_linked_after_matching_customer_signs_in_without_duplicate(): void
    {
        $quote = $this->publishedQuote();
        $this->post(route('quote.guest.lookup'), ['reference' => $quote->reference, 'email' => $quote->email]);
        // Simulates a quote/order created under the previous guest-only implementation.
        $oldOrder = \App\Models\Order::create([
            'user_id' => null,
            'order_number' => 'BQO-LEGACY-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
            'status' => 'quote_invoice_requested',
            'payment_status' => 'pending',
            'currency' => 'USD',
            'customer_name' => $quote->full_name,
            'customer_email' => $quote->email,
            'subtotal' => 600,
            'grand_total' => 710,
            'total_quantity' => 24,
            'idempotency_key' => 'guest-bulk-quote-'.$quote->id,
        ]);
        $quote->forceFill(['status' => BulkQuoteRequest::STATUS_ACCEPTED, 'guest_decision' => 'create_order', 'converted_order_id' => $oldOrder->id])->save();
        $customer = User::factory()->create(['email' => $quote->email, 'role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($customer, 'web')->post(route('quote.guest.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('quote.guest.review', $quote->reference));
        $this->assertSame($customer->id, $quote->fresh()->user_id);
        $this->assertSame($customer->id, $oldOrder->fresh()->user_id);
        $this->assertDatabaseCount('orders', 1);
    }

    private function publishedQuote(): BulkQuoteRequest
    {
        return $this->quote([
            'status' => 'quotation_sent',
            'quotation_version' => 1,
            'quotation_sent_at' => now(),
            'quotation_valid_until' => now()->addDays(20)->toDateString(),
            'quote_currency' => 'USD',
            'quoted_amount' => 710,
            'quotation_details' => ['quantity' => 24, 'subtotal' => 600, 'customization' => 80, 'shipping' => 30, 'payment_terms' => 'Invoice', 'shipping_method' => 'Express', 'quotation_notes' => 'Final quotation.'],
        ]);
    }

    private function quote(array $overrides = []): BulkQuoteRequest
    {
        return BulkQuoteRequest::create(array_merge([
            'reference' => 'BQ-'.strtoupper(fake()->unique()->bothify('????-####')),
            'full_name' => 'Alex', 'organization' => 'Blue FC', 'email' => 'bluefc@example.test',
            'phone' => '+1 555 0100', 'product_type' => 'Jerseys', 'estimated_quantity' => '10-49',
            'sizes_needed' => 'S-XL', 'artwork_details' => 'Blue and white custom jersey design',
            'shipping_address' => 'Boston USA', 'country' => 'United States', 'needed_by' => now()->addMonth()->toDateString(),
            'status' => 'new',
        ], $overrides));
    }
}
