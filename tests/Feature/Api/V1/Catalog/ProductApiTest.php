<?php

namespace Tests\Feature\Api\V1\Catalog;

use Database\Seeders\CatalogNavigationSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, ProductSeeder::class, CatalogNavigationSeeder::class]);
    }

    public function test_product_api_matches_legacy_filter_semantics_and_exposes_metadata(): void
    {
        $query = http_build_query([
            'product_types' => ['Performance Team Jersey'],
            'sort' => 'name-asc',
        ]);

        $this->get(route('products.index').'?'.$query)
            ->assertOk()
            ->assertSee('Custom Pro Team Jersey')
            ->assertDontSee('Custom Team Hoodie');

        $response = $this->getJson('/api/v1/products?'.$query);

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page', 'per_page', 'total', 'last_page',
                    'filters', 'active_filter_count', 'filter_options',
                    'sort_options', 'catalog_version',
                ],
                'request_id',
            ]);

        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('custom-pro-team-jersey'));
        $this->assertFalse($slugs->contains('custom-team-hoodie'));
        $this->assertSame(['Performance Team Jersey'], $response->json('meta.filters.product_types'));
    }

    public function test_product_detail_exposes_complete_non_sensitive_read_model(): void
    {
        $response = $this->getJson('/api/v1/products/custom-pro-team-jersey');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'custom-pro-team-jersey')
            ->assertJsonPath('data.sku', 'NPS-JER-PRO-001')
            ->assertJsonStructure([
                'data' => [
                    'id', 'slug', 'title', 'sku', 'summary', 'description',
                    'pricing', 'inventory', 'media', 'categories', 'attributes',
                    'features', 'specifications', 'reviews', 'configuration',
                    'fulfillment', 'seo', 'related_products',
                ],
                'meta' => ['catalog_version'],
                'request_id',
            ]);

        $payload = $response->json('data');
        $this->assertArrayNotHasKey('cost_price', $payload);
        $this->assertArrayNotHasKey('deleted_at', $payload);
        $this->assertArrayNotHasKey('flowtrack', $payload);
    }

    public function test_unknown_product_returns_api_not_found_contract(): void
    {
        $this->getJson('/api/v1/products/not-a-product')
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.')
            ->assertJsonStructure(['request_id']);
    }
}
