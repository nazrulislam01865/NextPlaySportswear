<?php

namespace Tests\Feature\Storefront;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\ArtworkProof;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArtworkApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['transactional_email.enabled' => false]);
    }

    public function test_admin_can_publish_proof_and_customer_can_review_and_download_it(): void
    {
        [$owner, $order, $item] = $this->orderForCustomer();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->post(route('admin.orders.artwork-proofs.store', $order), [
            'order_item_id' => $item->id,
            'design_name' => 'Raptors - White',
            'designer_notes' => 'Please check the logo and team numbers.',
            'proof_file' => UploadedFile::fake()->create('jersey-proof.pdf', 60, 'application/pdf'),
        ])->assertRedirect();

        $proof = ArtworkProof::query()->firstOrFail();
        $this->assertSame(1, $proof->version);
        $this->assertSame(ArtworkProof::PENDING, $proof->status);
        Storage::disk('local')->assertExists($proof->file_path);

        $this->asCustomer($owner)->get(route('account.artwork.index'))
            ->assertOk()->assertSee($order->order_number)->assertSee('Raptors - White');
        $this->asCustomer($owner)->get(route('account.artwork.show', $proof))
            ->assertOk()->assertSee('Review Artwork Proof')->assertSee('Please check the logo');
        $this->asCustomer($owner)->get(route('account.artwork.file', $proof).'?download=1')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_customers_cannot_view_or_change_an_artwork_proof(): void
    {
        [$owner, , $item] = $this->orderForCustomer();
        $proof = $this->proofFor($item);
        $other = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $this->asCustomer($other)->get(route('account.artwork.show', $proof))->assertForbidden();
        $this->asCustomer($other)->get(route('account.artwork.file', $proof))->assertForbidden();
        $this->asCustomer($other)->post(route('account.artwork.approve', $proof), ['confirm' => 1])->assertForbidden();
        $this->asCustomer($other)->post(route('account.artwork.changes.store', $proof), [
            'feedback' => 'Change the colors to green.', 'priority' => 'normal',
        ])->assertForbidden();
        $this->asCustomer($owner)->get(route('account.artwork.show', $proof))->assertOk();
    }

    public function test_customer_can_request_revision_with_private_reference_and_then_review_new_version(): void
    {
        [$owner, , $item] = $this->orderForCustomer();
        $first = $this->proofFor($item);
        $this->asCustomer($owner)->post(route('account.artwork.changes.store', $first), [
            'feedback' => 'Please change the shoulder stripes and make the team name larger.',
            'change_types' => ['color', 'text'],
            'priority' => 'urgent',
            'reference_file' => UploadedFile::fake()->create('annotation.pdf', 25, 'application/pdf'),
        ])->assertRedirect(route('account.artwork.show', $first));

        $first->refresh();
        $this->assertSame(ArtworkProof::REVISION, $first->status);
        $this->assertSame(['color', 'text'], $first->change_types);
        Storage::disk('local')->assertExists($first->reference_path);

        $this->asCustomer($owner)->get(route('account.artwork.file', $first).'?type=reference&download=1')->assertOk();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->post(route('admin.orders.artwork-proofs.store', $first->order), [
            'order_item_id' => $item->id,
            'proof_file' => UploadedFile::fake()->create('updated.pdf', 25, 'application/pdf'),
        ])->assertRedirect();
        $latest = ArtworkProof::query()->where('order_item_id', $item->id)->latest('version')->firstOrFail();
        $this->assertSame(2, $latest->version);
        $this->asCustomer($owner)->get(route('account.artwork.index'))->assertOk()->assertSee('v2')->assertSee('Pending Review');
        $this->asCustomer($owner)->post(route('account.artwork.approve', $first), ['confirm' => 1])->assertSessionHasErrors('proof');
        $this->asCustomer($owner)->get(route('account.artwork.show', $latest))->assertOk()->assertSee('Previous Versions (1)');
    }

    public function test_customer_approval_is_one_time_and_produces_confirmation(): void
    {
        [$owner, , $item] = $this->orderForCustomer();
        $proof = $this->proofFor($item);
        $this->asCustomer($owner)->post(route('account.artwork.approve', $proof), ['confirm' => 1])
            ->assertRedirect(route('account.artwork.approved', $proof));
        $this->assertSame(ArtworkProof::APPROVED, $proof->fresh()->status);
        $this->asCustomer($owner)->get(route('account.artwork.approved', $proof))->assertOk()->assertSee('Artwork Approved');
        $this->asCustomer($owner)->post(route('account.artwork.approve', $proof), ['confirm' => 1])->assertSessionHasErrors('proof');
    }

    public function test_unpaid_orders_cannot_be_approved_for_production(): void
    {
        [$owner, $order, $item] = $this->orderForCustomer();
        $order->update(['payment_status' => 'pending']);
        $proof = $this->proofFor($item);
        $this->asCustomer($owner)->post(route('account.artwork.approve', $proof), ['confirm' => 1])->assertSessionHasErrors('proof');
        $this->assertSame(ArtworkProof::PENDING, $proof->fresh()->status);
    }

    public function test_customer_can_reject_and_message_and_admin_can_reply(): void
    {
        [$owner, $order, $item] = $this->orderForCustomer();
        $proof = $this->proofFor($item);
        $this->asCustomer($owner)->post(route('account.artwork.message', $proof), [
            'message' => 'Can you check that the name is centered?',
        ])->assertRedirect();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->post(route('admin.orders.artwork-proofs.reply', [$order, $proof]), [
            'message' => 'Yes, we have centered the name.',
        ])->assertRedirect();
        $this->asCustomer($owner)->get(route('account.artwork.show', $proof))
            ->assertOk()->assertSee('Yes, we have centered the name.')
            ->assertSee('Messages with Design Team');
        $this->asCustomer($owner)->post(route('account.artwork.reject', $proof), [
            'feedback' => 'This proof is not the design we requested.',
        ])->assertRedirect();
        $this->assertSame(ArtworkProof::REJECTED, $proof->fresh()->status);
        $this->asCustomer($owner)->get(route('account.artwork.show', $proof))
            ->assertOk()->assertSee('This proof is not the design we requested.')
            ->assertSee('Yes, we have centered the name.');
        $this->actingAs($admin, 'admin')->get(route('admin.orders.show', $order))
            ->assertOk()->assertSee('Customer Feedback')
            ->assertSee('Yes, we have centered the name.')
            ->assertSee('Publish a New Proof Version');
    }

    public function test_customer_can_read_previous_design_team_replies_after_a_new_version_is_published(): void
    {
        [$owner, $order, $item] = $this->orderForCustomer();
        $first = $this->proofFor($item);
        $first->messages()->create(['sender_role' => 'designer', 'message' => 'We will update the jersey artwork.']);
        $first->update(['status' => ArtworkProof::REJECTED, 'customer_feedback' => 'Please redo the jersey artwork.']);

        $latest = $first->replicate();
        $latest->version = 2;
        $latest->status = ArtworkProof::PENDING;
        $latest->customer_feedback = null;
        $latest->save();

        $this->asCustomer($owner)->get(route('account.artwork.show', $latest))
            ->assertOk()->assertSee('We will update the jersey artwork.')
            ->assertSee('Messages with Design Team');
        $this->asCustomer($owner)->get(route('account.artwork.show', $first))
            ->assertOk()->assertSee('We will update the jersey artwork.');
    }

    public function test_admin_review_queue_shows_only_latest_proofs_and_filters_by_status(): void
    {
        [, $order, $item] = $this->orderForCustomer();
        $first = $this->proofFor($item);
        $first->update(['status' => ArtworkProof::REVISION, 'customer_feedback' => 'Please make the text larger.']);

        $latest = $first->replicate();
        $latest->version = 2;
        $latest->status = ArtworkProof::PENDING;
        $latest->design_name = 'Raptors Updated';
        $latest->customer_feedback = null;
        $latest->save();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->get(route('admin.artwork-approvals.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Raptors Updated')
            ->assertSee('Pending Review')
            ->assertDontSee('Please make the text larger.');

        $this->actingAs($admin, 'admin')->get(route('admin.artwork-approvals.index', [
            'q' => $order->order_number,
            'status' => ArtworkProof::REVISION,
        ]))->assertOk()->assertSee('No artwork proofs found.');
    }

    public function test_orders_view_staff_can_review_files_but_cannot_publish_or_reply(): void
    {
        [, $order, $item] = $this->orderForCustomer();
        $proof = $this->proofFor($item);
        $viewer = User::factory()->create(['role' => 'support_agent', 'is_active' => true]);

        $this->actingAs($viewer, 'admin')->get(route('admin.artwork-approvals.index'))->assertOk();
        $this->actingAs($viewer, 'admin')->get(route('admin.orders.artwork-proofs.file', [$order, $proof]))->assertOk();
        $this->actingAs($viewer, 'admin')->post(route('admin.orders.artwork-proofs.store', $order), [
            'order_item_id' => $item->id,
            'proof_file' => UploadedFile::fake()->create('new.pdf', 5, 'application/pdf'),
        ])->assertForbidden();
        $this->actingAs($viewer, 'admin')->post(route('admin.orders.artwork-proofs.reply', [$order, $proof]), [
            'message' => 'This reply must be restricted.',
        ])->assertForbidden();
    }

    private function asCustomer(User $user): static
    {
        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => (int) $user->auth_session_version]);
        return $this->actingAs($user, 'web');
    }

    /** @return array{User, Order, OrderItem} */
    private function orderForCustomer(): array
    {
        $owner = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $order = Order::query()->create([
            'user_id' => $owner->id,
            'order_number' => 'NP-'.Str::upper(Str::random(10)),
            'status' => 'proof_approval',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => $owner->name,
            'customer_email' => $owner->email,
            'subtotal' => 60,
            'grand_total' => 60,
            'total_quantity' => 2,
            'placed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_name' => 'Custom Basketball Jersey',
            'quantity' => 2,
            'unit_price' => 30,
            'line_total' => 60,
        ]);
        return [$owner, $order, $item];
    }

    private function proofFor(OrderItem $item): ArtworkProof
    {
        Storage::disk('local')->put('artwork-proofs/testing/proof.pdf', '%PDF-1.4 test content');
        return ArtworkProof::query()->create([
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'version' => 1,
            'status' => ArtworkProof::PENDING,
            'design_name' => 'Raptors - White',
            'file_path' => 'artwork-proofs/testing/proof.pdf',
            'original_name' => 'proof.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 25,
        ]);
    }
}
