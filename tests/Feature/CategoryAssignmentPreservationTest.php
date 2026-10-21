<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryProductAssignmentSyncService;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\LeafCategoryAssignmentService;
use App\Services\Storefront\CategoryCatalogService;
use Database\Seeders\CatalogNavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class CategoryAssignmentPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_product_details_keeps_additional_category_placements(): void
    {
        $main = $this->category('Basketball Uniforms');
        $extra = $this->category('Basketball Fan Jerseys');
        app(CategoryTreeService::class)->rebuildClosure();
        $product = $this->product('Team Jersey', $main);
        $this->assign($product, $main, true);
        $this->assign($product, $extra, false);

        // The product editor submits the primary category but does not expose
        // secondary assignments configured through the category product manager.
        $method = new ReflectionMethod(ProductController::class, 'syncCatalogAssignments');
        $method->invoke(app(ProductController::class), $product, [
            'primary_category_id' => $main->id,
            'category_assignments' => [$main->id],
            'show_in_category_page' => true,
        ]);

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $extra->id,
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $main->id,
            'is_primary' => true,
        ]);
    }

    public function test_category_assignment_repair_does_not_erase_extra_or_pivot_only_placements(): void
    {
        $primary = $this->category('Soccer Uniforms');
        $secondary = $this->category('Soccer Training Apparel');
        app(CategoryTreeService::class)->rebuildClosure();
        $product = $this->product('Soccer Polo', $primary);
        $this->assign($product, $primary, true);
        $this->assign($product, $secondary, false);

        // Even legacy callers requesting a "reset" must be non-destructive.
        app(CategoryProductAssignmentSyncService::class)
            ->syncAllProductCategoryAssignments(resetExisting: true);

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $secondary->id,
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $primary->id,
        ]);
    }

    public function test_adding_child_category_never_removes_previously_assigned_products(): void
    {
        $formerLeaf = $this->category('Basketball Fan Jerseys');
        $product = $this->product('Fan Jersey', $formerLeaf);
        $this->assign($product, $formerLeaf, true);
        $this->category('Custom Basketball Fan Jerseys', $formerLeaf);
        app(CategoryTreeService::class)->rebuildClosure();

        app(LeafCategoryAssignmentService::class)->enforce();

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $formerLeaf->id,
        ]);
        $this->assertSame($formerLeaf->id, (int) $product->fresh()->category_id);
    }

    public function test_catalog_seeder_keeps_a_previously_assigned_category_after_it_gains_children(): void
    {
        $formerLeaf = $this->category('Basketball Fan Jerseys');
        $otherCategory = $this->category('Basketball Uniforms');
        app(CategoryTreeService::class)->rebuildClosure();
        $product = $this->product('Basketball Fan Jersey', $formerLeaf);
        $this->assign($product, $formerLeaf, true);
        $this->assign($product, $otherCategory, false);

        DB::table('category_product')
            ->where('product_id', $product->id)
            ->where('category_id', $formerLeaf->id)
            ->update(['is_featured' => true, 'sort_order' => 47]);
        DB::table('category_product')
            ->where('product_id', $product->id)
            ->where('category_id', $otherCategory->id)
            ->update(['sort_order' => 19]);

        $this->category('Custom Fan Jerseys', $formerLeaf);
        app(CategoryTreeService::class)->rebuildClosure();

        $method = new ReflectionMethod(CatalogNavigationSeeder::class, 'syncProductCategories');
        // Re-seeding should be safe even if the hierarchy has been edited.
        $method->invoke(app(CatalogNavigationSeeder::class));
        $method->invoke(app(CatalogNavigationSeeder::class));

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $formerLeaf->id,
            'is_primary' => true,
            'is_featured' => true,
            'sort_order' => 47,
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $otherCategory->id,
            'is_primary' => false,
            'sort_order' => 19,
        ]);
        $this->assertSame(2, $product->categories()->count());
        $this->assertSame($formerLeaf->id, (int) $product->fresh()->category_id);

        $cards = app(CategoryCatalogService::class)->categoriesByIds([$formerLeaf->id], 1);
        $this->assertSame(1, (int) ($cards[0]['product_count'] ?? 0));
    }

    public function test_pending_legacy_leaf_migration_never_drops_historical_placements(): void
    {
        $formerlyLeaf = $this->category('Soccer Training Apparel');
        $otherCategory = $this->category('Soccer Uniforms');
        app(CategoryTreeService::class)->rebuildClosure();
        $product = $this->product('Training Top', $formerlyLeaf);
        $this->assign($product, $formerlyLeaf, true);
        $this->assign($product, $otherCategory, false);
        DB::table('category_product')
            ->where('product_id', $product->id)
            ->where('category_id', $formerlyLeaf->id)
            ->update(['is_featured' => true, 'sort_order' => 63]);

        $this->category('Youth Training Tops', $formerlyLeaf);
        app(CategoryTreeService::class)->rebuildClosure();

        $migration = require database_path('migrations/2026_07_27_000001_enforce_leaf_category_product_assignments.php');
        $migration->up();

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $formerlyLeaf->id,
            'is_primary' => true,
            'is_featured' => true,
            'sort_order' => 63,
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $otherCategory->id,
            'is_primary' => false,
        ]);
        $this->assertSame(2, $product->categories()->count());
    }

    private function assign(Product $product, Category $category, bool $primary): void
    {
        DB::table('category_product')->insert([
            'category_id' => $category->id,
            'product_id' => $product->id,
            'is_primary' => $primary,
            'is_featured' => false,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function category(string $name, ?Category $parent = null): Category
    {
        return Category::query()->create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'display_type' => 'collection',
            'category_type' => 'standard',
            'page_template' => 'product_grid',
            'status' => 'active',
            'description' => $name.' description',
            'image_url' => '',
            'image_alt' => $name,
            'cta_label' => 'View Category',
            'is_active' => true,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
            'sort_order' => 0,
        ]);
    }

    private function product(string $name, Category $category): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'sku' => 'CAT-PRESERVE-'.Str::upper(Str::random(10)),
            'status' => 'active',
            'base_price' => 10,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_active' => true,
        ]);
    }
}
