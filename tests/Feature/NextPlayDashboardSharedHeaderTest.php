<?php
namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayDashboardSharedHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_the_standard_admin_header_without_a_second_header(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $response->assertOk()->assertSee('Sales analytics')
            ->assertSee('data-admin-header-copy', false)
            ->assertSee('data-admin-storefront-link', false)
            ->assertSee('View storefront')
            ->assertDontSee('np-dash-topbar');

        $html = $response->getContent();
        $this->assertSame(1, preg_match_all('/<header\b[^>]*\bdata-admin-page-header\b/i', $html));
        $this->assertSame(1, preg_match_all('/<nav\b[^>]*\bdata-admin-sidebar-nav\b/i', $html));
    }
}
