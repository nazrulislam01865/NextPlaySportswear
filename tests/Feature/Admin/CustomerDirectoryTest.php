<?php

namespace Tests\Feature\Admin;

use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_customer_directory_without_admin_accounts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'name' => 'Operations Admin',
            'email' => 'operations@example.test',
        ]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'name' => 'Customer One',
            'email' => 'customer-one@example.test',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.customers.index'));

        $response->assertOk()
            ->assertSee('Customer Directory')
            ->assertSee($customer->name)
            ->assertSee($customer->email)
            ->assertDontSee('operations@example.test');
    }

    public function test_customer_details_include_profile_address_and_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'name' => 'Jordan Customer',
            'email' => 'jordan@example.test',
            'phone' => '+1 555 0100',
            'company_name' => 'Jordan Sports',
        ]);

        CustomerAddress::query()->create([
            'user_id' => $customer->id,
            'type' => 'shipping',
            'first_name' => 'Jordan',
            'last_name' => 'Customer',
            'address_line_1' => '100 Team Street',
            'city' => 'Boston',
            'state' => 'MA',
            'country' => 'US',
            'postal_code' => '02108',
            'is_default' => true,
        ]);

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'NP-'.Str::upper(Str::random(10)),
            'status' => 'completed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'currency' => 'USD',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 10,
            'tax_total' => 0,
            'grand_total' => 110,
            'total_quantity' => 1,
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.customers.show', $customer));

        $response->assertOk()
            ->assertSee('Account Profile')
            ->assertSee('Jordan Sports')
            ->assertSee('100 Team Street')
            ->assertSee($order->order_number);
    }

    public function test_catalog_manager_cannot_access_customer_directory(): void
    {
        $catalogManager = User::factory()->create(['role' => 'catalog_manager', 'is_active' => true]);

        $this->actingAs($catalogManager, 'admin')
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }

    public function test_admin_user_cannot_be_opened_as_customer_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.show', $otherAdmin))
            ->assertNotFound();
    }

    public function test_admin_can_suspend_customer_and_invalidate_authentication_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 4,
            'remember_token' => 'remember-me-token',
        ]);

        $response = $this->actingAs($admin, 'admin')->patch(
            route('admin.customers.suspend', $customer),
            ['suspension_reason' => 'Repeated payment abuse investigation.']
        );

        $response->assertRedirect(route('admin.customers.show', $customer));

        $customer->refresh();

        $this->assertFalse($customer->is_active);
        $this->assertSame(5, (int) $customer->auth_session_version);
        $this->assertNull($customer->remember_token);
        $this->assertSame('Repeated payment abuse investigation.', $customer->suspension_reason);
        $this->assertSame($admin->id, $customer->suspended_by);
        $this->assertNotNull($customer->suspended_at);
    }

    public function test_admin_can_reactivate_suspended_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
            'auth_session_version' => 7,
            'suspended_at' => now()->subDay(),
            'suspension_reason' => 'Manual review.',
        ]);

        $response = $this->actingAs($admin, 'admin')->patch(
            route('admin.customers.reactivate', $customer)
        );

        $response->assertRedirect(route('admin.customers.show', $customer));

        $customer->refresh();

        $this->assertTrue($customer->is_active);
        $this->assertSame(8, (int) $customer->auth_session_version);
        $this->assertSame($admin->id, $customer->reactivated_by);
        $this->assertNotNull($customer->reactivated_at);
        $this->assertSame('Manual review.', $customer->suspension_reason);
    }

    public function test_order_manager_can_view_customers_but_cannot_suspend_them_by_default(): void
    {
        $orderManager = User::factory()->create(['role' => 'order_manager', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $this->actingAs($orderManager, 'admin')
            ->get(route('admin.customers.show', $customer))
            ->assertOk();

        $this->actingAs($orderManager, 'admin')
            ->patch(route('admin.customers.suspend', $customer), [
                'suspension_reason' => 'Should not be permitted.',
            ])
            ->assertForbidden();

        $this->assertTrue($customer->fresh()->is_active);
    }
}
