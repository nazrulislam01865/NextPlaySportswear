<?php

namespace Tests\Feature\Api\V1\Catalog;

use Database\Seeders\CatalogNavigationSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, ProductSeeder::class, CatalogNavigationSeeder::class]);
    }

    public function test_category_collection_exposes_hierarchy_counts_and_sports(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['categories', 'sports', 'filter_tags'],
                'meta' => ['catalog_version'],
                'request_id',
            ]);

        $categories = collect($response->json('data.categories'));
        $teamUniforms = $categories->firstWhere('slug', 'team-uniforms');

        $this->assertNotNull($teamUniforms);
        $this->assertGreaterThanOrEqual(2, (int) $teamUniforms['product_count']);
        $this->assertNotEmpty($response->json('data.sports'));
    }

    public function test_category_detail_uses_recursive_listing_and_exposes_filter_metadata(): void
    {
        $response = $this->getJson('/api/v1/categories/team-uniforms');

        $response->assertOk()
            ->assertJsonPath('data.category.slug', 'team-uniforms')
            ->assertJsonStructure([
                'data' => [
                    'category',
                    'breadcrumbs',
                    'related_categories',
                    'products' => ['data', 'meta'],
                    'filters',
                    'filter_options',
                    'sort_options',
                    'seo',
                ],
                'request_id',
            ]);

        $slugs = collect($response->json('data.products.data'))->pluck('slug');
        $this->assertTrue($slugs->contains('baseball-uniform-set-for-teams'));
        $this->assertTrue($slugs->contains('sublimated-soccer-kit-for-teams'));
        $this->assertFalse($slugs->contains('custom-team-hoodie'));
    }

    public function test_unknown_category_returns_api_not_found_contract(): void
    {
        $this->getJson('/api/v1/categories/not-a-category')
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.')
            ->assertJsonStructure(['request_id']);
    }
}
