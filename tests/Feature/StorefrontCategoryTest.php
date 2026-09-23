<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryTreeService;
use Database\Seeders\CatalogNavigationSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            CategorySeeder::class,
            ProductSeeder::class,
            CatalogNavigationSeeder::class,
        ]);
    }

    public function test_category_index_is_database_driven(): void
    {
        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Browse Categories')
            ->assertSee('All Product Categories')
            ->assertSee('Custom Team Uniforms')
            ->assertSee('Shop by Sport')
            ->assertDontSee('Quick navigation')
            ->assertDontSee('Choose a main category to browse its subcategories and product categories.')
            ->assertDontSee('All product groups')
            ->assertDontSee('Select a main category above to see its subcategories and product categories, or view every group below.')
            ->assertSee(route('categories.show', 'team-uniforms'), false);
    }

    public function test_category_index_hides_zero_product_branches(): void
    {
        $parent = Category::query()->where('slug', 'team-uniforms')->firstOrFail();

        Category::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Empty Storefront Category',
            'slug' => 'empty-storefront-category',
            'display_type' => 'collection',
            'category_type' => 'standard',
            'page_template' => 'product_grid',
            'status' => 'active',
            'description' => 'This category intentionally has no products.',
            'is_active' => true,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
            'sort_order' => 999,
        ]);

        Category::query()->create([
            'name' => 'Empty Root Category',
            'slug' => 'empty-root-category',
            'display_type' => 'collection',
            'category_type' => 'standard',
            'page_template' => 'product_grid',
            'status' => 'active',
            'description' => 'This root intentionally has no products or subcategories.',
            'is_active' => true,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
            'sort_order' => 999,
        ]);

        app(CategoryTreeService::class)->flushCache();

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertDontSee('Empty Storefront Category')
            ->assertDontSee('Empty Root Category');
    }

    public function test_category_index_groups_descendants_under_real_parent_categories(): void
    {
        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('data-parent-category="team-uniforms"', false)
            ->assertSee('data-parent-category="custom-jerseys"', false)
            ->assertSee('Baseball Uniforms')
            ->assertSee('Soccer Kits')
            ->assertSee('Football Jerseys')
            ->assertSee('Basketball Jerseys');
    }

    public function test_parent_category_aggregates_products_from_reachable_descendants(): void
    {
        $this->get(route('categories.show', 'team-uniforms'))
            ->assertOk()
            ->assertSee('Baseball Uniform Set for Teams')
            ->assertSee('Sublimated Soccer Kit')
            ->assertDontSee('Custom Team Hoodie');
    }

    public function test_category_page_exposes_only_admin_enabled_facets(): void
    {
        $this->get(route('categories.show', 'custom-jerseys'))
            ->assertOk()
            ->assertSee('Color Family')
            ->assertSee('Product Size')
            ->assertSee('Production Time')
            ->assertSee('Free Setup');
    }

    public function test_attribute_filter_is_applied_through_catalog_assignments(): void
    {
        $this->get(route('categories.show', [
            'slug' => 'custom-jerseys',
            'attributes' => ['color' => ['navy']],
        ]))
            ->assertOk()
            ->assertSee('Custom Pro Team Jersey')
            ->assertSee('Custom Football Jersey with Name &amp; Number', false);
    }

    public function test_active_child_is_not_reachable_beneath_an_inactive_parent(): void
    {
        Category::query()->where('slug', 'custom-jerseys')->update([
            'status' => 'inactive',
            'is_active' => false,
        ]);
        app(CategoryTreeService::class)->flushCache();

        $this->get(route('categories.show', 'football-jerseys'))->assertNotFound();
    }

    public function test_products_support_multiple_categories_with_one_primary_assignment(): void
    {
        $product = Product::query()->where('sku', 'NPS-JER-PRO-001')->firstOrFail();
        $primary = $product->categories()->wherePivot('is_primary', true)->first();

        $this->assertGreaterThanOrEqual(1, $product->categories()->count());
        $this->assertNotNull($primary);
        $this->assertSame('basketball-jerseys', $primary->slug);
        $product->categories->each(fn (Category $category) => $this->assertTrue($category->isLeaf()));
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->get(route('categories.show', [
            'slug' => 'football-jerseys',
            'sort' => 'unsafe-sort',
        ]))
            ->assertRedirect()
            ->assertSessionHasErrors('sort');
    }

    public function test_unknown_category_returns_not_found(): void
    {
        $this->get('/category/not-a-category')->assertNotFound();
    }
}
