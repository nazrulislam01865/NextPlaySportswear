<?php

namespace Tests\Feature\Api\V1\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_api_rejects_invalid_sort_and_price_ranges_as_json(): void
    {
        $this->getJson('/api/v1/products?sort=unsafe-sort')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['sort'], 'request_id']);

        $this->getJson('/api/v1/products?min_price=20&max_price=10')
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['max_price'], 'request_id']);
    }

    public function test_product_api_rejects_invalid_attribute_slugs(): void
    {
        $this->getJson('/api/v1/products?attributes[Unsafe_Key][]=value')
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['attributes'], 'request_id']);
    }

    public function test_search_suggestion_query_is_bounded(): void
    {
        $this->getJson('/api/v1/search/suggestions?q='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['q'], 'request_id']);
    }
}
