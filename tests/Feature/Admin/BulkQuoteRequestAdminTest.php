<?php

namespace Tests\Feature\Admin;

use App\Models\BulkQuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkQuoteRequestAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_admin_can_view_and_search_bulk_quote_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $matching = $this->quote(['reference' => 'BQ-TEST-MATCH', 'organization' => 'Northside FC']);
        $this->quote(['reference' => 'BQ-TEST-OTHER', 'organization' => 'Other Club']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.bulk-quotes.index', [
            'q' => 'Northside',
        ]));

        $response->assertOk()
            ->assertSee('Requested Bulk Quotes')
            ->assertSee($matching->reference)
            ->assertDontSee('BQ-TEST-OTHER');
    }

    public function test_catalog_manager_cannot_open_bulk_quote_admin_pages(): void
    {
        $catalogManager = User::factory()->create(['role' => 'catalog_manager', 'is_active' => true]);
        $quote = $this->quote();

        $this->actingAs($catalogManager, 'admin')
            ->get(route('admin.bulk-quotes.show', $quote))
            ->assertForbidden();
    }

    public function test_bulk_quote_detail_shows_complete_customer_product_delivery_and_sync_information(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'name' => 'Jordan Customer',
            'email' => 'jordan@example.test',
        ]);

        $quote = $this->quote([
            'user_id' => $customer->id,
            'customer_account' => [
                'is_registered' => true,
                'source_user_id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'company_name' => 'Northside Sports',
                'preferred_sport' => 'Football',
            ],
            'full_name' => 'Jordan Customer',
            'email' => 'jordan@example.test',
            'product_type' => 'Custom football jerseys',
            'customization_types' => ['logo', 'names-numbers'],
            'artwork_details' => 'Use the club crest on the left chest and sponsor on front.',
            'shipping_address' => '100 Team Street, Boston, MA 02108',
            'additional_notes' => 'Please confirm production timing before payment.',
            'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_SYNCED,
            'flowtrack_inquiry_number' => 'INQ-260911-1001',
            'flowtrack_inquiry_id' => 1001,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.bulk-quotes.show', $quote));

        $response->assertOk()
            ->assertSee($quote->reference)
            ->assertSee('Jordan Customer')
            ->assertSee('Custom football jerseys')
            ->assertSee('Names &amp; Numbers', false)
            ->assertSee('100 Team Street')
            ->assertSee('INQ-260911-1001')
            ->assertSee('Please confirm production timing');
    }

    public function test_admin_can_manage_bulk_quote_workflow_with_assignment_and_activity_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $assignee = User::factory()->create(['role' => 'order_manager', 'is_active' => true]);
        $quote = $this->quote();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.bulk-quotes.update', $quote), [
                'status' => BulkQuoteRequest::STATUS_QUALIFIED,
                'priority' => BulkQuoteRequest::PRIORITY_HIGH,
                'assigned_to' => $assignee->id,
                'quoted_amount' => '2750.50',
                'quote_currency' => 'usd',
                'admin_note' => 'Pricing confirmed with the production team.',
                'last_contacted_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $quote->refresh();

        $this->assertSame(BulkQuoteRequest::STATUS_QUALIFIED, $quote->status);
        $this->assertSame(BulkQuoteRequest::PRIORITY_HIGH, $quote->priority);
        $this->assertSame($assignee->id, $quote->assigned_to);
        $this->assertSame('USD', $quote->quote_currency);
        $this->assertSame('2750.50', $quote->quoted_amount);
        $this->assertDatabaseHas('bulk_quote_activities', [
            'bulk_quote_request_id' => $quote->id,
            'actor_id' => $admin->id,
            'action' => 'status_updated',
            'from_status' => BulkQuoteRequest::STATUS_NEW,
            'to_status' => BulkQuoteRequest::STATUS_QUALIFIED,
        ]);
    }

    public function test_support_agent_can_view_but_cannot_change_bulk_quote_workflow(): void
    {
        $support = User::factory()->create(['role' => 'support_agent', 'is_active' => true]);
        $quote = $this->quote();

        $this->actingAs($support, 'admin')
            ->get(route('admin.bulk-quotes.show', $quote))
            ->assertOk();

        $this->actingAs($support, 'admin')
            ->patch(route('admin.bulk-quotes.update', $quote), [
                'status' => BulkQuoteRequest::STATUS_QUALIFIED,
                'priority' => BulkQuoteRequest::PRIORITY_HIGH,
                'quote_currency' => 'USD',
            ])
            ->assertForbidden();

        $this->assertSame(BulkQuoteRequest::STATUS_NEW, $quote->fresh()->status);
    }

    public function test_admin_can_retry_bulk_quote_flowtrack_sync_and_persist_remote_identity(): void
    {
        config()->set('flowtrack.enabled', true);
        config()->set('flowtrack.base_url', 'https://flowtrack.example.test');
        config()->set('flowtrack.token', 'test-token');
        config()->set('flowtrack.endpoints.inquiries', '/api/integrations/nextplay/inquiries');
        config()->set('flowtrack.queue.enabled', false);

        Http::fake([
            'https://flowtrack.example.test/*' => Http::response([
                'ok' => true,
                'message' => 'Inquiry accepted.',
                'data' => [
                    'inquiry_id' => 4201,
                    'inquiry_number' => 'INQ-4201',
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $quote = $this->quote(['flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_FAILED]);
        $originalUpdatedAt = $quote->updated_at?->copy();

        $this->travel(1)->minute();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.bulk-quotes.retry-sync', $quote))
            ->assertRedirect()
            ->assertSessionHas('status');

        $quote->refresh();

        $this->assertSame(BulkQuoteRequest::FLOWTRACK_SYNC_SYNCED, $quote->flowtrack_sync_status);
        $this->assertSame(1, $quote->flowtrack_sync_attempts);
        $this->assertSame(4201, $quote->flowtrack_inquiry_id);
        $this->assertSame('INQ-4201', $quote->flowtrack_inquiry_number);
        $this->assertNull($quote->flowtrack_sync_error);
        $this->assertNotNull($quote->flowtrack_synced_at);
        $this->assertTrue($originalUpdatedAt?->equalTo($quote->updated_at) ?? false);
    }

    public function test_customer_account_cannot_be_selected_as_bulk_quote_assignee(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $quote = $this->quote();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.bulk-quotes.show', $quote))
            ->patch(route('admin.bulk-quotes.update', $quote), [
                'status' => BulkQuoteRequest::STATUS_UNDER_REVIEW,
                'priority' => BulkQuoteRequest::PRIORITY_NORMAL,
                'assigned_to' => $customer->id,
                'quote_currency' => 'USD',
            ])
            ->assertRedirect(route('admin.bulk-quotes.show', $quote))
            ->assertSessionHasErrors('assigned_to');

        $this->assertNull($quote->fresh()->assigned_to);
    }

    public function test_admin_can_download_bulk_quote_attachment_through_protected_route(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $path = 'bulk-quotes/team-roster.xlsx';
        Storage::disk('public')->put($path, 'fake-excel-content');

        $quote = $this->quote([
            'attachment' => [
                'path' => $path,
                'original_name' => 'team-roster.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'size' => 18,
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.bulk-quotes.attachment', $quote));

        $response->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="team-roster.xlsx"')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    /** @param array<string,mixed> $overrides */
    private function quote(array $overrides = []): BulkQuoteRequest
    {
        return BulkQuoteRequest::query()->create(array_merge([
            'reference' => 'BQ-'.strtoupper(fake()->unique()->bothify('????-####')),
            'full_name' => 'Alex Morgan',
            'organization' => 'Next Team',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+1 555 0100',
            'product_type' => 'Team jerseys',
            'estimated_quantity' => '100-499',
            'sizes_needed' => 'S, M, L, XL',
            'budget_range' => '1500-5000',
            'artwork_details' => 'Team logo and player names are required for the order.',
            'customization_types' => ['logo'],
            'shipping_address' => '100 Team Street, Boston, MA 02108',
            'country' => 'United States',
            'state_province' => 'MA',
            'postal_code' => '02108',
            'preferred_shipping_method' => 'express',
            'needed_by' => now()->addMonth()->toDateString(),
            'event_date' => now()->addMonths(2)->toDateString(),
            'additional_notes' => 'Call before finalizing.',
            'status' => BulkQuoteRequest::STATUS_NEW,
            'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_PENDING,
            'flowtrack_sync_attempts' => 0,
        ], $overrides));
    }
}
