<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryProductAssignmentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogFacetAccuracyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_category_counts_are_distinct_scoped_and_self_excluding(): void
    {
        $accessories = $this->category('Accessories', 'accessories');
        $flags = $this->category('Flags, Scarves & Pennants', 'flags-scarves-pennants', parent: $accessories);
        $towels = $this->category('Towels', 'towels', parent: $accessories);

        $madeToOrder = $this->product('Men Team Flag', 'men-team-flag', false);
        $madeToOrder->categories()->attach([
            $flags->id => ['is_primary' => true, 'sort_order' => 0],
            $towels->id => ['is_primary' => false, 'sort_order' => 1],
        ]);

        $inStock = $this->product('Men Team Towel', 'men-team-towel', true);
        $inStock->categories()->attach($towels->id, ['is_primary' => true, 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/products?q=men&availability[]=made-to-order&categories[]='.$flags->id);
        $response->assertOk()->assertJsonPath('meta.total', 1);

        $categories = collect($response->json('meta.filter_options.categories'));
        $accessoriesFacet = $categories->firstWhere('id', $accessories->id);
        $children = collect($accessoriesFacet['children'] ?? []);

        $this->assertSame(1, (int) ($accessoriesFacet['count'] ?? 0));
        $this->assertSame(1, (int) ($children->firstWhere('id', $flags->id)['count'] ?? 0));
        $this->assertSame(1, (int) ($children->firstWhere('id', $towels->id)['count'] ?? 0));
    }


    public function test_sibling_category_counts_are_independent_and_parent_is_the_distinct_union(): void
    {
        $accessories = $this->category('Accessories', 'accessories');
        $flags = $this->category('Flags, Scarves & Pennants', 'flags-scarves-pennants', parent: $accessories);
        $towels = $this->category('Towels', 'towels', parent: $accessories);
        $neckwear = $this->category('Neckwear & Armwear', 'neckwear-armwear', parent: $accessories);

        $flag = $this->product('Men Team Flag', 'men-team-flag', false);
        $flag->categories()->attach($flags->id, ['is_primary' => true, 'sort_order' => 0]);

        $towelOne = $this->product('Men Team Towel One', 'men-team-towel-one', false);
        $towelOne->categories()->attach($towels->id, ['is_primary' => true, 'sort_order' => 0]);

        $towelTwo = $this->product('Men Team Towel Two', 'men-team-towel-two', false);
        $towelTwo->categories()->attach($towels->id, ['is_primary' => true, 'sort_order' => 0]);

        $neck = $this->product('Men Team Neckwear', 'men-team-neckwear', false);
        $neck->categories()->attach($neckwear->id, ['is_primary' => true, 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/products?q=men');
        $response->assertOk()->assertJsonPath('meta.total', 4);

        $categories = collect($response->json('meta.filter_options.categories'));
        $accessoriesFacet = $categories->firstWhere('id', $accessories->id);
        $children = collect($accessoriesFacet['children'] ?? []);

        $this->assertSame(4, (int) ($accessoriesFacet['count'] ?? 0));
        $this->assertSame(1, (int) ($children->firstWhere('id', $flags->id)['count'] ?? 0));
        $this->assertSame(2, (int) ($children->firstWhere('id', $towels->id)['count'] ?? 0));
        $this->assertSame(1, (int) ($children->firstWhere('id', $neckwear->id)['count'] ?? 0));
    }

    public function test_assignment_repair_removes_stale_sibling_rows_using_trusted_leaf_fields(): void
    {
        $accessories = $this->category('Accessories', 'accessories');
        $flags = $this->category('Flags, Scarves & Pennants', 'flags-scarves-pennants', parent: $accessories);
        $towels = $this->category('Towels', 'towels', parent: $accessories);
        $neckwear = $this->category('Neckwear & Armwear', 'neckwear-armwear', parent: $accessories);

        $product = $this->product('Men Team Flag', 'men-team-flag', false);
        $product->update([
            'category_id' => $accessories->id,
            'subcategory_id' => $flags->id,
        ]);
        $product->categories()->attach([
            $flags->id => ['is_primary' => true, 'sort_order' => 0],
            $towels->id => ['is_primary' => false, 'sort_order' => 1],
            $neckwear->id => ['is_primary' => false, 'sort_order' => 2],
        ]);

        app(CategoryProductAssignmentSyncService::class)->syncAllProductCategoryAssignments(resetExisting: true);

        $this->assertSame(
            [$flags->id],
            $product->fresh()->categories()->pluck('categories.id')->map(fn ($id): int => (int) $id)->sort()->values()->all()
        );

        $response = $this->getJson('/api/v1/products?q=men');
        $response->assertOk()->assertJsonPath('meta.total', 1);

        $categories = collect($response->json('meta.filter_options.categories'));
        $accessoriesFacet = $categories->firstWhere('id', $accessories->id);
        $children = collect($accessoriesFacet['children'] ?? []);

        $this->assertSame(1, (int) ($accessoriesFacet['count'] ?? 0));
        $this->assertSame(1, (int) ($children->firstWhere('id', $flags->id)['count'] ?? 0));
        $this->assertNull($children->firstWhere('id', $towels->id));
        $this->assertNull($children->firstWhere('id', $neckwear->id));
    }

    public function test_product_type_facet_ignores_its_own_selection_but_respects_other_filters(): void
    {
        $category = $this->category('Performance Apparel', 'performance-apparel');

        $jersey = $this->product('Men Custom Jersey', 'men-custom-jersey', false, 'Jersey');
        $jersey->categories()->attach($category->id, ['is_primary' => true, 'sort_order' => 0]);

        $cap = $this->product('Men Ready Cap', 'men-ready-cap', true, 'Cap');
        $cap->categories()->attach($category->id, ['is_primary' => true, 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/products?q=men&product_types[]=Jersey');
        $response->assertOk()->assertJsonPath('meta.total', 1);

        $types = collect($response->json('meta.filter_options.product_types'));
        $this->assertSame(1, (int) ($types->firstWhere('value', 'Jersey')['count'] ?? 0));
        $this->assertSame(1, (int) ($types->firstWhere('value', 'Cap')['count'] ?? 0));
    }

    private function category(string $name, string $slug, ?Category $parent = null): Category
    {
        return Category::query()->create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => $slug,
            'display_type' => 'collection',
            'category_type' => 'standard',
            'status' => 'active',
            'is_active' => true,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
        ]);
    }

    private function product(string $name, string $slug, bool $inStock, string $productType = 'Accessory'): Product
    {
        return Product::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sku' => strtoupper(str_replace('-', '-', $slug)),
            'status' => 'active',
            'product_type' => $productType,
            'base_price' => 20,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_active' => true,
            'is_customizable' => false,
            'track_inventory' => $inStock,
            'stock_quantity' => $inStock ? 10 : 0,
            'allow_backorder' => false,
            'published_at' => now()->subMinute(),
        ]);
    }
}
