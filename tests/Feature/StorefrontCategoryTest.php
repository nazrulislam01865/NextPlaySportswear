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
            ->assertSee(route('categories.show', 'team-uniforms'), false);
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

        $this->assertGreaterThanOrEqual(2, $product->categories()->count());
        $this->assertNotNull($primary);
        $this->assertSame('basketball-jerseys', $primary->slug);
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
