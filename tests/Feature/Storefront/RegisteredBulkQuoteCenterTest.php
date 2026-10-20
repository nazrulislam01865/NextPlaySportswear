<?php

namespace Tests\Feature\Storefront;

use App\Models\BulkQuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisteredBulkQuoteCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('transactional_email.enabled', false);
        config()->set('transactional_email.recipients.sales', '');
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true, 'email_verified_at' => now()]);
    }

    private function quote(User $owner, array $attributes = []): BulkQuoteRequest
    {
        return BulkQuoteRequest::create(array_merge([
            'user_id' => $owner->id,
            'reference' => 'BQ-'.strtoupper(fake()->unique()->bothify('????-####')),
            'full_name' => $owner->name,
            'organization' => 'Raptors Youth Club',
            'email' => $owner->email,
            'phone' => '+1 555 0150',
            'product_type' => 'Custom Jerseys',
            'estimated_quantity' => '10-49',
            'artwork_details' => 'Team logo and numbers',
            'shipping_address' => 'Boston, MA',
            'country' => 'United States',
            'status' => 'new',
        ], $attributes));
    }

    private function published(User $owner): BulkQuoteRequest
    {
        return $this->quote($owner, [
            'status' => 'quotation_sent',
            'quotation_version' => 1,
            'quotation_sent_at' => now(),
            'quotation_valid_until' => now()->addDays(21)->toDateString(),
            'quoted_amount' => 749,
            'quote_currency' => 'USD',
            'quotation_details' => [
                'quantity' => 24, 'subtotal' => 704, 'customization' => 0, 'shipping' => 45,
                'shipping_method' => 'Express', 'payment_terms' => 'Invoice', 'quotation_notes' => 'Custom sportswear',
            ],
        ]);
    }

    public function test_account_routes_require_login_and_quotes_are_owner_scoped(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $quote = $this->published($owner);
        $this->get(route('account.bulk-quotes.index'))->assertRedirect();
        $this->actingAs($other, 'web');
        $this->get(route('account.bulk-quotes.index'))->assertOk()->assertDontSee($quote->reference);
        $this->get(route('account.bulk-quotes.show', $quote->reference))->assertNotFound();
        $this->get(route('account.bulk-quotes.changes', $quote->reference))->assertNotFound();
        $this->post(route('account.bulk-quotes.decision', $quote->reference), ['decision' => 'create_order'])->assertNotFound();
        $this->post(route('account.bulk-quotes.message', $quote->reference), ['body' => 'Unauthorized'])->assertNotFound();
    }

    public function test_quote_list_filters_and_review_render_real_data(): void
    {
        $owner = $this->customer();
        $pending = $this->quote($owner);
        $ready = $this->published($owner);
        $this->actingAs($owner, 'web');
        $this->get(route('account.bulk-quotes.index'))->assertOk()->assertSee($pending->reference)->assertSee($ready->reference)->assertSee('Bulk Quotes');
        $this->get(route('account.bulk-quotes.index', ['status' => 'pending']))->assertOk()->assertSee($pending->reference)->assertDontSee($ready->reference);
        $this->get(route('account.bulk-quotes.index', ['status' => 'sent']))->assertOk()->assertDontSee($pending->reference)->assertSee($ready->reference);
        $this->get(route('account.bulk-quotes.show', $ready->reference))->assertOk()->assertSee('749.00')->assertSee('Quotation Summary');
        $this->get(route('account.bulk-quotes.show', $pending->reference))->assertOk()->assertSee('preparing your pricing');
    }

    public function test_admin_publishes_for_registered_customer_and_files_remain_private(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $quote = $this->quote($owner);
        $this->actingAs($admin, 'admin')->post(route('admin.bulk-quotes.publish', $quote), [
            'quantity' => 24, 'subtotal' => 600, 'customization' => 80, 'shipping' => 30,
            'valid_until' => now()->addDays(20)->toDateString(),
            'payment_terms' => 'Invoice',
            'files' => [UploadedFile::fake()->create('pricing.pdf', 25, 'application/pdf')],
        ])->assertRedirect();
        $this->assertSame(1, $quote->fresh()->quotation_version);
        $file = $quote->quoteFiles()->firstOrFail();
        $this->actingAs($other, 'web')->get(route('account.bulk-quotes.file', [$quote->reference, $file]))->assertNotFound();
        $this->actingAs($owner, 'web')->get(route('account.bulk-quotes.file', [$quote->reference, $file]))->assertOk();
        $this->get(route('account.bulk-quotes.show', $quote->reference))->assertOk()->assertSee('pricing.pdf');
    }

    public function test_change_request_is_visible_to_admin_and_order_requires_customer_acceptance(): void
    {
        $owner = $this->customer();
        $quote = $this->published($owner);
        $this->actingAs($owner, 'web')->post(route('account.bulk-quotes.changes.send', $quote->reference), [
            'change_types' => ['pricing', 'delivery'],
            'body' => 'Please update quantity to 30 pieces and confirm delivery.',
        ])->assertRedirect(route('account.bulk-quotes.show', $quote->reference));
        $this->assertSame('customer_responded', $quote->fresh()->status);
        $this->assertSame('changes_requested', $quote->fresh()->guest_decision);
        $this->assertDatabaseHas('bulk_quote_messages', [
            'bulk_quote_request_id' => $quote->id, 'actor_id' => $owner->id, 'kind' => 'change_request',
        ]);
        $this->post(route('account.bulk-quotes.decision', $quote->reference), ['decision' => 'create_order'])->assertSessionHasErrors('quote');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_confirm_without_order_then_create_single_account_order(): void
    {
        $owner = $this->customer();
        $quote = $this->published($owner);
        $this->actingAs($owner, 'web')->post(route('account.bulk-quotes.decision', $quote->reference), [
            'decision' => 'accept_only',
        ])->assertRedirect(route('account.bulk-quotes.show', $quote->reference));
        $this->assertNull($quote->fresh()->converted_order_id);
        $this->post(route('account.bulk-quotes.decision', $quote->reference), ['decision' => 'create_order'])
            ->assertRedirect(route('account.bulk-quotes.show', $quote->reference));
        $id = $quote->fresh()->converted_order_id;
        $this->assertNotNull($id);
        $this->assertDatabaseHas('orders', ['id' => $id, 'user_id' => $owner->id, 'payment_status' => 'pending']);
        $this->post(route('account.bulk-quotes.decision', $quote->reference), ['decision' => 'create_order'])->assertRedirect();
        $this->assertSame($id, $quote->fresh()->converted_order_id);
        $this->assertDatabaseCount('orders', 1);
    }
}
