<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class VueHomepageCutoverTest extends TestCase
{
    public function test_home_route_mounts_the_new_vue_storefront_without_legacy_home_markup(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="nextplay-storefront"', false)
            ->assertDontSee('class="home-page"', false);
    }

    public function test_legacy_latest_products_endpoint_remains_registered_for_rollback_compatibility(): void
    {
        $route = Route::getRoutes()->getByName('home.latest-products');

        $this->assertNotNull($route);
        $this->assertSame('homepage/latest-products', $route->uri());
    }
}
