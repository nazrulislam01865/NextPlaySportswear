<?php

namespace Tests\Feature\Admin;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_menu_update_collapses_exact_duplicate_top_level_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $menu = Menu::query()->create([
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.menus.update', $menu), [
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => '1',
            'items' => [
                $this->routeItem('home', 'Home', 'home', 0),
                $this->routeItem('products-a', 'All Products', 'products.index', 10),
                $this->routeItem('products-b', 'All Products', 'products.index', 20),
                $this->routeItem('how-a', 'How It Works', 'how-to-order', 30),
                $this->routeItem('how-b', 'How It Works', 'how-to-order', 40),
                $this->routeItem('quote-a', 'Bulk Quote', 'quote.request', 50, 'text-brand-red'),
                $this->routeItem('quote-b', 'Bulk Quote', 'quote.request', 60, 'text-brand-red'),
            ],
        ]);

        $response->assertRedirect(route('admin.menus.edit', $menu));
        $response->assertSessionHasNoErrors();

        $this->assertSame(1, $menu->allItems()->where('route_name', 'products.index')->count());
        $this->assertSame(1, $menu->allItems()->where('route_name', 'how-to-order')->count());
        $this->assertSame(1, $menu->allItems()->where('route_name', 'quote.request')->count());
        $this->assertSame(4, $menu->allItems()->count());
    }

    public function test_storefront_navigation_hides_exact_duplicate_header_rows(): void
    {
        $menu = Menu::query()->create([
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => true,
        ]);

        foreach ([
            ['Home', 'home', 0, ''],
            ['All Products', 'products.index', 10, ''],
            ['All Products', 'products.index', 20, ''],
            ['How It Works', 'how-to-order', 30, ''],
            ['How It Works', 'how-to-order', 40, ''],
            ['Bulk Quote', 'quote.request', 50, 'text-brand-red'],
            ['Bulk Quote', 'quote.request', 60, 'text-brand-red'],
        ] as [$label, $routeName, $sortOrder, $cssClass]) {
            $menu->allItems()->create([
                'label' => $label,
                'link_type' => 'route',
                'route_name' => $routeName,
                'target' => '_self',
                'css_class' => $cssClass,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        $navigation = app(\App\Services\Catalog\NavigationService::class);
        $navigation->flushCache();
        $labels = $navigation->items('header-primary')->pluck('label')->all();

        $this->assertSame(['Home', 'All Products', 'How It Works', 'Bulk Quote'], $labels);
    }

    public function test_header_menu_update_discards_stale_shop_descendants_before_validation(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $menu = Menu::query()->create([
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.menus.update', $menu), [
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => '1',
            'items' => [
                $this->routeItem('shop', 'Shop Products', 'categories.index', 0),
                [
                    'key' => 'legacy-shop-child',
                    'parent_key' => 'shop',
                    'label' => 'Legacy category child',
                    'link_type' => 'category',
                    'category_id' => '',
                    'route_name' => '',
                    'url' => '',
                    'target' => '_self',
                    'css_class' => '',
                    'is_active' => '1',
                    'sort_order' => 0,
                ],
                $this->routeItem('products', 'All Products', 'products.index', 10),
            ],
        ]);

        $response->assertRedirect(route('admin.menus.edit', $menu));
        $response->assertSessionHasNoErrors();

        $this->assertSame(2, $menu->allItems()->count());
        $this->assertDatabaseMissing('menu_items', ['label' => 'Legacy category child']);
    }

    public function test_menu_validation_error_is_rendered_once(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $menu = Menu::query()->create([
            'name' => 'Primary Header',
            'slug' => 'primary-header',
            'location' => 'header-primary',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.menus.edit', $menu))
            ->followingRedirects()
            ->put(route('admin.menus.update', $menu), [
                'name' => 'Primary Header',
                'slug' => 'primary-header',
                'location' => 'header-primary',
                'is_active' => '1',
                'items' => [[
                    'key' => 'invalid-category',
                    'parent_key' => '',
                    'label' => 'Invalid Category',
                    'link_type' => 'category',
                    'category_id' => '',
                    'route_name' => '',
                    'url' => '',
                    'target' => '_self',
                    'css_class' => '',
                    'is_active' => '1',
                    'sort_order' => 0,
                ]],
            ]);

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Choose a category for this menu item.'));
    }

    private function routeItem(
        string $key,
        string $label,
        string $routeName,
        int $sortOrder,
        string $cssClass = '',
    ): array {
        return [
            'key' => $key,
            'parent_key' => '',
            'label' => $label,
            'link_type' => 'route',
            'category_id' => '',
            'route_name' => $routeName,
            'url' => '',
            'target' => '_self',
            'css_class' => $cssClass,
            'is_active' => '1',
            'sort_order' => $sortOrder,
        ];
    }
}
