<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDownload;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_only_their_own_order(): void
    {
        $owner = $this->customer();
        $otherCustomer = $this->customer();
        $order = $this->orderFor($owner);

        $this->actingAs($owner, 'web')
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($otherCustomer, 'web')
            ->get(route('account.orders.show', $order))
            ->assertForbidden();
    }

    public function test_invoice_download_requires_customer_ownership_and_a_valid_signature(): void
    {
        $owner = $this->customer();
        $order = $this->orderFor($owner);

        $this->actingAs($owner, 'web')
            ->get(route('account.orders.invoice.download', $order))
            ->assertForbidden();

        $signedUrl = URL::temporarySignedRoute(
            'account.orders.invoice.download',
            now()->addMinutes(10),
            ['order' => $order],
        );

        $this->actingAs($owner, 'web')
            ->get($signedUrl)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_only_order_managers_can_open_commerce_operations(): void
    {
        $order = $this->orderFor($this->customer());
        $catalogManager = User::factory()->create(['role' => 'catalog_manager', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($catalogManager, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_shipment_status_updates_drive_order_fulfillment_without_duplicate_allocation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $order = $this->orderFor($this->customer(), [
            'status' => 'in_production',
            'payment_status' => 'paid',
        ]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.orders.shipments.store', $order), [
            'status' => 'preparing',
            'carrier' => 'UPS',
            'items' => [
                ['id' => $item->id, 'quantity' => $item->quantity],
            ],
        ])->assertRedirect();

        $shipment = OrderShipment::query()->firstOrFail();
        $this->assertSame('in_production', $order->fresh()->status);
        $this->assertSame('partial', $order->fresh()->fulfillment_status);
        $this->assertSame(0, $item->fresh()->fulfilled_quantity);

        $this->actingAs($admin, 'admin')->patch(route('admin.orders.shipments.update', [$order, $shipment]), [
            'status' => 'in_transit',
            'carrier' => 'UPS',
        ])->assertRedirect();

        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('fulfilled', $order->fresh()->fulfillment_status);
        $this->assertSame(0, $item->fresh()->fulfilled_quantity);

        $this->actingAs($admin, 'admin')->patch(route('admin.orders.shipments.update', [$order, $shipment]), [
            'status' => 'delivered',
            'carrier' => 'UPS',
        ])->assertRedirect();

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame($item->quantity, $item->fresh()->fulfilled_quantity);
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_customer_can_submit_only_eligible_delivered_quantities_for_return(): void
    {
        Storage::fake('local');
        $customer = $this->customer();
        $order = $this->orderFor($customer, [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'delivered_at' => now(),
        ]);
        $item = $order->items()->firstOrFail();
        $item->update(['fulfilled_quantity' => $item->quantity]);

        $this->actingAs($customer, 'web')->post(route('account.orders.returns.store', $order), [
            'reason_code' => 'size_issue',
            'requested_resolution' => 'refund',
            'reason' => 'One jersey does not fit.',
            'confirm_accuracy' => '1',
            'evidence' => [UploadedFile::fake()->create('fit-photo.jpg', 100, 'image/jpeg')],
            'items' => [
                ['id' => $item->id, 'quantity' => 1, 'condition' => 'unused', 'note' => 'Tags attached.'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('order_return_requests', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => 'return',
            'status' => 'requested',
        ]);
        $this->assertDatabaseHas('order_return_items', [
            'order_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $attachment = \App\Models\OrderReturnAttachment::query()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->file_path);
    }

    public function test_private_download_enforces_owner_signature_limit_and_private_storage(): void
    {
        Storage::fake('local');
        $customer = $this->customer();
        $otherCustomer = $this->customer();
        $order = $this->orderFor($customer);
        Storage::disk('local')->put('order-downloads/test/artwork.pdf', 'private file');

        $download = OrderDownload::query()->create([
            'order_id' => $order->id,
            'token' => Str::random(48),
            'title' => 'Approved Artwork',
            'file_path' => 'order-downloads/test/artwork.pdf',
            'original_name' => 'artwork.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
            'download_limit' => 1,
            'download_count' => 0,
            'is_active' => true,
        ]);

        $url = URL::temporarySignedRoute(
            'account.downloads.download',
            now()->addMinutes(10),
            ['download' => $download],
        );

        $this->actingAs($otherCustomer, 'web')->get($url)->assertForbidden();
        $this->actingAs($customer, 'web')->get($url)->assertDownload('artwork.pdf');
        $this->assertSame(1, $download->fresh()->download_count);
        $this->actingAs($customer, 'web')->get($url)->assertGone();
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true]);
    }

    private function orderFor(User $customer, array $overrides = []): Order
    {
        $order = Order::query()->create(array_merge([
            'user_id' => $customer->id,
            'order_number' => 'NP-'.Str::upper(Str::random(10)),
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 10,
            'tax_total' => 5,
            'grand_total' => 115,
            'total_quantity' => 2,
            'placed_at' => now(),
        ], $overrides));

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Custom Team Jersey',
            'product_slug' => 'custom-team-jersey',
            'sku' => 'NP-JERSEY-1',
            'quantity' => 2,
            'unit_price' => 50,
            'customization_unit_price' => 0,
            'line_total' => 100,
            'customization' => [],
            'is_digital' => false,
        ]);

        return $order->fresh('items');
    }
}
