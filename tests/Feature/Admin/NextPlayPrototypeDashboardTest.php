<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayPrototypeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_reference_sections_and_real_store_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'name' => 'Dashboard Administrator']);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Real Customer']);
        $this->createOrder($customer, 'NP-TEST-100', 'BDT', 7250, now());

        $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, Dashboard!')
            ->assertSee('Sales analytics')
            ->assertSee('Order status')
            ->assertSee('Recent orders')
            ->assertSee('Top-selling products')
            ->assertSee('Bulk quote pipeline')
            ->assertSee('Fulfillment overview')
            ->assertSee('Recent notifications')
            ->assertSee('Store health')
            ->assertSee('Heads up')
            ->assertSee('NP-TEST-100')
            ->assertSee('BDT')
            ->assertSee('7,250.00')
            ->assertSee('Real Customer');
    }

    public function test_date_range_is_validated_and_export_contains_real_daily_totals(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->createOrder($customer, 'NP-THIS-WEEK', 'USD', 125, now());
        $this->createOrder($customer, 'NP-OLDER', 'USD', 75, now()->subDays(12));

        $page = $this->actingAs($admin, 'admin')->get(route('admin.dashboard', ['days' => 7]));
        $page->assertOk()->assertSee('NP-THIS-WEEK')->assertDontSee('NP-OLDER');
        $this->actingAs($admin, 'admin')->get(route('admin.dashboard', ['days' => 999]))
            ->assertOk()->assertViewHas('days', 30);

        $csv = $this->actingAs($admin, 'admin')->get(route('admin.dashboard', ['days' => 7, 'export' => 'csv']));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Revenue (USD)', $content);
        $this->assertStringContainsString('125', $content);
    }

    public function test_sales_revenue_does_not_mix_currencies(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->createOrder($customer, 'NP-BDT', 'BDT', 9000, now()->subHour());
        $this->createOrder($customer, 'NP-USD', 'USD', 125, now());

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $response->assertOk()->assertSee('USD')->assertSee('125.00')->assertDontSee('9,125.00');
    }

    private function createOrder(User $customer, string $number, string $currency, float $total, $placedAt): void
    {
        Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => $number,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'status' => 'in_production',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => $currency,
            'subtotal' => $total,
            'grand_total' => $total,
            'total_quantity' => 1,
            'placed_at' => $placedAt,
        ]);
    }
}
