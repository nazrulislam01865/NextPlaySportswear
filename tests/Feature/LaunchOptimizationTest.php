<?php

namespace Tests\Feature;

use App\Services\Storefront\ProductCatalogService;
use App\Support\HomepageSectionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Mockery\MockInterface;
use Tests\TestCase;

class LaunchOptimizationTest extends TestCase
{
    use RefreshDatabase;
    public function test_public_media_route_is_registered_once(): void
    {
        $matches = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => $route->getName() === 'media.public')
            ->values();

        $this->assertCount(1, $matches);
        $this->assertSame('media/{path}', $matches->first()?->uri());
    }

    public function test_launch_hidden_placeholder_routes_are_not_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNotNull($routes->getByName('password.request'));
        $this->assertNotNull($routes->getByName('password.email'));
        $this->assertNotNull($routes->getByName('password.reset'));
        $this->assertNotNull($routes->getByName('password.store'));
        $this->assertNull($routes->getByName('account.section'));
    }



    public function test_login_page_restores_the_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false)
            ->assertSee('Forgot password?');
    }

    public function test_header_contains_a_wishlist_hover_preview_region(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('data-wishlist-preview', false)
            ->assertSee('Wishlist Preview')
            ->assertSee('Your wishlist is empty');
    }

    public function test_header_cart_uses_the_saved_snapshot_without_loading_a_full_product(): void
    {
        $this->mock(ProductCatalogService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('findFullBySlug');
        });

        $this->withSession([
            'nextplay_cart' => [
                'items' => [[
                    'key' => 'snapshot-item',
                    'product_slug' => 'snapshot-product',
                    'quantity' => 2,
                    'unit_price' => 25,
                    'line_subtotal' => 50,
                    'customization_total' => 5,
                    'product' => [
                        'title' => 'Snapshot Product',
                        'short_title' => 'Snapshot Product',
                        'image' => '/images/product-placeholder.svg',
                        'alt' => 'Snapshot Product',
                        'url' => '/products/snapshot-product',
                    ],
                ]],
            ],
        ])->get(route('faq'))
            ->assertOk()
            ->assertSee('aria-label="Shopping cart, 2 items"', false)
            ->assertSee('Snapshot Product');
    }

    public function test_storefront_output_contains_launch_seo_and_accessibility_metadata(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('href="#main-content"', false)
            ->assertSee('rel="sitemap"', false)
            ->assertSee('name="twitter:image:alt"', false)
            ->assertSee('id="main-content"', false);
    }

    public function test_order_again_uses_an_independent_named_rate_limiter(): void
    {
        $route = app('router')->getRoutes()->getByName('account.orders.reorder.store');

        $this->assertNotNull($route);
        $this->assertContains('throttle:order-reorder', $route->gatherMiddleware());
        $this->assertNotContains('throttle:10,1', $route->gatherMiddleware());
    }

    public function test_every_homepage_overview_payload_contains_optional_text_keys(): void
    {
        foreach (HomepageSectionRegistry::orderedDefinitions() as $definition) {
            $section = HomepageSectionRegistry::mergeForView((string) $definition['key']);

            $this->assertArrayHasKey('title', $section);
            $this->assertArrayHasKey('description', $section);
            $this->assertArrayHasKey('eyebrow', $section);
            $this->assertArrayHasKey('primary_label', $section);
            $this->assertArrayHasKey('secondary_label', $section);
        }
    }
}
