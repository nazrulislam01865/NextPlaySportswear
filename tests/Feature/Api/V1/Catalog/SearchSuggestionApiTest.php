<?php

namespace Tests\Feature\Api\V1\Catalog;

use Database\Seeders\CatalogNavigationSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SearchSuggestionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, ProductSeeder::class, CatalogNavigationSeeder::class]);
    }

    public function test_empty_search_returns_empty_structured_groups(): void
    {
        $this->getJson('/api/v1/search/suggestions?q=')
            ->assertOk()
            ->assertJsonPath('data.products', [])
            ->assertJsonPath('data.categories', [])
            ->assertJsonPath('meta.query', '');
    }

    public function test_search_returns_structured_product_and_category_suggestions(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions?q=jersey');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['products', 'categories'],
                'meta' => ['query', 'product_count', 'category_count', 'catalog_version'],
                'request_id',
            ]);

        $this->assertNotEmpty($response->json('data.products'));
        $this->assertNotEmpty($response->json('data.categories'));
        $this->assertSame('product', $response->json('data.products.0.type'));
        $this->assertSame('category', $response->json('data.categories.0.type'));
    }

    public function test_search_suggestion_route_has_dedicated_rate_limit(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.search.suggestions');

        $this->assertNotNull($route);
        $this->assertContains('throttle:catalog-search-suggestions', $route->gatherMiddleware());
    }
}
