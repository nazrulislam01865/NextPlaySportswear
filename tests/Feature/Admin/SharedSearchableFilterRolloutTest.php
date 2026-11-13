<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedSearchableFilterRolloutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The searchable menu is presentation-only: GET names, selected values,
     * permission gates, and downstream controllers stay unchanged.
     */
    public function test_seven_admin_pages_render_reusable_filter_selects(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $pages = [
            'admin.media-library.index' => ['type', 'date', 'category'],
            'admin.categories.index' => ['status', 'type'],
            'admin.orders.index' => ['status', 'payment_status'],
            'admin.bulk-quotes.index' => ['status', 'priority', 'assigned_to', 'sync_status', 'country'],
            'admin.contact-requests.index' => ['status', 'topic'],
            'admin.returns.index' => ['status', 'type'],
            'admin.customers.index' => ['account_status', 'verification', 'orders', 'marketing', 'sort'],
        ];

        foreach ($pages as $routeName => $names) {
            $response = $this->actingAs($admin, 'admin')->get(route($routeName));
            $response->assertOk()
                ->assertSee('data-np-admin-select', false)
                ->assertSee('nextplay-admin-searchable-select.css', false)
                ->assertSee('nextplay-admin-searchable-select.js', false);

            foreach ($names as $name) {
                $response->assertSee('name="'.$name.'"', false);
            }
        }
    }

    public function test_get_filter_values_remain_selected_in_native_inputs(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.customers.index', ['sort' => 'name', 'verification' => 'verified']))
            ->assertOk()
            ->assertSee('value="name" selected', false)
            ->assertSee('value="verified" selected', false);

        $this->get(route('admin.categories.index', ['status' => 'active', 'type' => 'standard']))
            ->assertOk()
            ->assertSee('value="active" selected', false)
            ->assertSee('value="standard" selected', false);
    }

    public function test_media_gallery_preserves_alpine_filter_bindings_and_page_only_assets(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.media-library.index'));

        $response->assertOk()
            ->assertSee('x-model="type"', false)
            ->assertSee('x-model="date"', false)
            ->assertSee('x-model="category"', false)
            ->assertSee('x-on:change="load(1)"', false)
            ->assertSee('np-admin-select-sync', false);
    }
}
