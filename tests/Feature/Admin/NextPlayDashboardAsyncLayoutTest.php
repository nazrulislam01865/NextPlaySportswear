<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayDashboardAsyncLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_update_returns_only_the_dashboard_fragment_with_real_filtered_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        foreach (['NOW' => now(), 'OLDER' => now()->subDays(12)] as $suffix => $date) {
            Order::query()->create([
                'user_id' => $customer->id,
                'order_number' => 'NP-DASH-'. $suffix,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'status' => 'in_production',
                'payment_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'currency' => 'USD',
                'subtotal' => 25,
                'grand_total' => 25,
                'total_quantity' => 1,
                'placed_at' => $date,
            ]);
        }

        $response = $this->actingAs($admin, 'admin')->withHeaders([
            'X-NextPlay-Dashboard' => 'partial',
            'Accept' => 'text/html',
        ])->get(route('admin.dashboard', ['days' => 7]));

        $response->assertOk()
            ->assertViewIs('admin.partials.dashboard-content')
            ->assertViewHas('days', 7)
            ->assertSee('data-dashboard-days="7"', false)
            ->assertSee('NP-DASH-NOW')
            ->assertDontSee('NP-DASH-OLDER')
            ->assertDontSee('<!doctype html>', false)
            ->assertDontSee('data-admin-sidebar-nav', false)
            ->assertDontSee('np-dash-topbar', false);
    }

    public function test_regular_dashboard_and_csv_export_still_use_the_existing_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))
            ->assertOk()->assertViewIs('admin.dashboard')
            ->assertSee('data-admin-sidebar-nav', false)
            ->assertSee('Sales analytics');

        $csv = $this->actingAs($admin, 'admin')->get(route('admin.dashboard', [
            'days' => 7, 'export' => 'csv',
        ]));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));
    }
}
