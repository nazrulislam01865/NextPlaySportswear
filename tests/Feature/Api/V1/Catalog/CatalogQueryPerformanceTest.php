<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_listing_query_count_does_not_grow_with_each_row(): void
    {
        $category = $this->category();
        $this->product($category, 1);

        $oneProductQueries = $this->queryCountFor('/api/v1/products');

        foreach (range(2, 10) as $index) {
            $this->product($category, $index);
        }

        $tenProductQueries = $this->queryCountFor('/api/v1/products');

        $this->assertLessThanOrEqual(
            $oneProductQueries + 5,
            $tenProductQueries,
            "Catalog list query count grew from {$oneProductQueries} to {$tenProductQueries}; check for N+1 loading."
        );
    }

    public function test_filter_facet_query_count_does_not_grow_with_each_category_option(): void
    {
        $firstCategory = $this->category();
        $this->product($firstCategory, 1);

        $oneCategoryQueries = $this->queryCountFor('/api/v1/products?q=Performance');

        foreach (range(2, 12) as $index) {
            $category = Category::query()->create([
                'name' => 'Performance Category '.$index,
                'slug' => 'performance-category-'.$index,
                'display_type' => 'collection',
                'category_type' => 'standard',
                'status' => 'active',
                'description' => 'Facet query-count regression category.',
                'image_url' => '/media/catalog/categories/performance-'.$index.'.jpg',
                'image_alt' => 'Performance Category '.$index,
                'is_active' => true,
                'is_visible_in_catalog' => true,
                'is_visible_in_menu' => true,
            ]);
            $this->product($category, $index);
        }

        $twelveCategoryQueries = $this->queryCountFor('/api/v1/products?q=Performance');

        $this->assertLessThanOrEqual(
            $oneCategoryQueries + 4,
            $twelveCategoryQueries,
            "Facet query count grew from {$oneCategoryQueries} to {$twelveCategoryQueries}; category/sport facets must use grouped counts, not per-option queries."
        );
    }

    public function test_product_detail_has_a_bounded_relation_query_count(): void
    {
        $category = $this->category();
        $this->product($category, 1, 'detail-product');

        $queries = $this->queryCountFor('/api/v1/products/detail-product');

        $this->assertLessThanOrEqual(30, $queries, "Product detail executed {$queries} queries; expected eager-loaded bounded relations.");
    }

    private function queryCountFor(string $uri): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson($uri)->assertOk();
        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => 'Performance Apparel',
            'slug' => 'performance-apparel',
            'display_type' => 'collection',
            'category_type' => 'standard',
            'status' => 'active',
            'description' => 'Performance catalog category used by query-count regression tests.',
            'image_url' => '/media/catalog/categories/performance-apparel.jpg',
            'image_alt' => 'Performance Apparel',
            'is_active' => true,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
        ]);
    }

    private function product(Category $category, int $index, ?string $slug = null): Product
    {
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Performance Product '.$index,
            'slug' => $slug ?: 'performance-product-'.$index,
            'sku' => 'PERF-'.$index,
            'status' => 'active',
            'short_description' => 'Performance test product.',
            'base_price' => 20,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_active' => true,
            'is_customizable' => false,
            'track_inventory' => false,
            'stock_quantity' => 0,
            'allow_backorder' => true,
            'published_at' => now()->subMinute(),
        ]);

        $product->categories()->attach($category->id, ['is_primary' => true, 'sort_order' => 0]);

        return $product;
    }
}
