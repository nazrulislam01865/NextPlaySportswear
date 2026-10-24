<?php

namespace Tests\Feature\Admin;

use App\Models\Menu;
use App\Models\User;
use App\Services\Catalog\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderShippingActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_header_menu_gets_one_editable_shipping_action(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.menus.store'), [
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => '1',
            'items' => [],
        ]);
        $menu = Menu::query()->where('location', 'header-primary')->firstOrFail();

        $response->assertRedirect(route('admin.menus.edit', $menu));
        $this->assertSame(1, $menu->allItems()->where('route_name', 'shipping')->count());
        $this->assertSame('USA SHIPPING', $menu->allItems()->where('route_name', 'shipping')->first()->label);
    }

    public function test_shipping_action_uses_admin_managed_destination_and_is_not_repeated_in_standard_menu(): void
    {
        $menu = Menu::query()->create([
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => true,
        ]);
        $menu->allItems()->create([
            'label' => 'Fast Delivery',
            'link_type' => 'custom',
            'url' => '/shipping-delivery',
            'target' => '_self',
            'css_class' => 'np-shipping-action',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $navigation = app(NavigationService::class);
        $navigation->flushCache();
        $this->assertCount(1, $navigation->items('header-primary')->where('label', 'Fast Delivery'));
        $this->assertSame('/shipping-delivery', $navigation->items('header-primary')->firstWhere('label', 'Fast Delivery')->resolvedUrl());
    }

    public function test_duplicate_header_rows_with_different_css_are_collapsed_at_save(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $menu = Menu::query()->create([
            'name' => 'Primary Header', 'slug' => 'primary-header',
            'location' => 'header-primary', 'is_active' => true,
        ]);
        $make = fn (string $key, string $css): array => [
            'key' => $key, 'parent_key' => '', 'label' => 'Bulk Quote',
            'link_type' => 'route', 'route_name' => 'quote.request', 'category_id' => '',
            'url' => '', 'target' => '_self', 'css_class' => $css,
            'is_active' => '1', 'sort_order' => 10,
        ];

        $this->actingAs($admin, 'admin')->put(route('admin.menus.update', $menu), [
            'name' => 'Primary Header', 'slug' => 'primary-header',
            'location' => 'header-primary', 'is_active' => '1',
            'items' => [$make('quote-a', ''), $make('quote-b', 'text-brand-red')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $menu->allItems()->where('route_name', 'quote.request')->count());
    }
}
