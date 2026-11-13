<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceFormGuidelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_six_commerce_pages_share_the_admin_form_guideline(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $routes = [
            'admin.orders.index',
            'admin.bulk-quotes.index',
            'admin.contact-requests.index',
            'admin.returns.index',
            'admin.customers.index',
            'admin.reward-program.edit',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin, 'admin')->get(route($route))
                ->assertOk()
                ->assertSee('np-commerce-form-system', false)
                ->assertSee('nextplay-admin-commerce-forms.css', false);
        }
    }

    public function test_search_inputs_retain_existing_get_names_and_values(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        foreach (['admin.orders.index', 'admin.bulk-quotes.index', 'admin.contact-requests.index', 'admin.customers.index'] as $route) {
            $this->actingAs($admin, 'admin')->get(route($route, ['q' => 'Example request']))
                ->assertOk()
                ->assertSee('name="q"', false)
                ->assertSee('value="Example request"', false)
                ->assertSee('np-admin-filter-search', false);
        }
    }

    public function test_customers_sort_and_actions_stay_in_separate_layout_regions(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['sort' => 'name']))
            ->assertOk()
            ->assertSee('np-customer-filters__sort', false)
            ->assertSee('np-customer-filters__actions', false)
            ->assertSee('value="name" selected', false)
            ->assertSee('Filter customers');
    }

    public function test_returns_filter_button_is_in_action_group_not_stretched_grid_column(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.returns.index', ['type' => 'exchange']))
            ->assertOk()
            ->assertSee('admin-filter-bar__actions', false)
            ->assertSee('value="exchange" selected', false)
            ->assertSee('Filter');
    }

    public function test_rewards_settings_fields_and_permission_control_remain_intact(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->get(route('admin.reward-program.edit'))
            ->assertOk()
            ->assertSee('np-commerce-rewards__form', false)
            ->assertSee('name="default_progress_target"', false)
            ->assertSee('name="referral_minimum_order"', false)
            ->assertSee('Save rewards settings');
    }
}
