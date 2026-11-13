<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayDashboardSharedSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_the_same_original_full_sidebar_as_other_admin_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'name' => 'Dashboard Administrator',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $response->assertOk()
            ->assertSee('Main Menu')
            ->assertSee('Manage Store')
            ->assertSee('Sign out')
            ->assertSee('Sales analytics')
            ->assertDontSee('All admin tools');

        $html = $response->getContent();
        // Count actual sidebar <nav> elements, not occurrences inside inline JavaScript
        // (sidebar-scroll-restore also queries '[data-admin-sidebar-nav]').
        $navCount = preg_match_all('/<nav\b[^>]*\bdata-admin-sidebar-nav\b/i', $html);
        $this->assertSame(1, $navCount, 'Dashboard must have one full shared sidebar, not two menus.');
        $this->assertStringContainsString('admin-sidebar-resizer', $html);
        $this->assertStringContainsString('nextplay-dashboard-prototype.css', $html);
    }
}
