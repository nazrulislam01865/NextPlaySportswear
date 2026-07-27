<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\CategoryDeletionService;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\LeafCategoryAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryDeletionAndLeafAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_parent_deletes_its_subtree_and_makes_affected_products_categoryless(): void
    {
        Storage::fake('public');

        $root = $this->category('Teamwear');
        $child = $this->category('Jerseys', $root);
        $leaf = $this->category('Football Jerseys', $child);
        $unrelatedLeaf = $this->category('Accessories');
        app(CategoryTreeService::class)->rebuildClosure();

        $product = $this->product('Custom Football Jersey', $root, $leaf);
        $product->categories()->attach([
            $leaf->id => ['is_primary' => true, 'is_featured' => false, 'sort_order' => 0],
            $unrelatedLeaf->id => ['is_primary' => false, 'is_featured' => false, 'sort_order' => 1],
        ]);

        $menuId = DB::table('menus')->insertGetId([
            'name' => 'Main menu',
            'slug' => 'main-menu',
            'location' => 'header',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $menuItemId = DB::table('menu_items')->insertGetId([
            'menu_id' => $menuId,
            'parent_id' => null,
            'label' => 'Football Jerseys',
            'link_type' => 'category',
            'category_id' => $leaf->id,
            'route_name' => null,
            'url' => null,
            'target' => '_self',
            'css_class' => null,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(CategoryDeletionService::class);
        $impact = $service->impactsFor(collect([$root]))[$root->id];

        $this->assertSame(3, $impact['category_count']);
        $this->assertSame(2, $impact['child_category_count']);
        $this->assertSame(1, $impact['product_count']);
        $this->assertSame(1, $impact['menu_item_count']);
        $this->assertContains('Jerseys', $impact['category_names']);
        $this->assertContains('Football Jerseys', $impact['category_names']);
        $this->assertContains('Custom Football Jersey', $impact['product_names']);

        $service->deleteSubtree($root);

        $this->assertSoftDeleted('categories', ['id' => $root->id]);
        $this->assertSoftDeleted('categories', ['id' => $child->id]);
        $this->assertSoftDeleted('categories', ['id' => $leaf->id]);
        $this->assertDatabaseHas('categories', ['id' => $unrelatedLeaf->id, 'deleted_at' => null]);
        $this->assertDatabaseMissing('category_closure', ['ancestor_id' => $root->id]);
        $this->assertDatabaseMissing('category_closure', ['descendant_id' => $leaf->id]);

        $product->refresh();
        $this->assertNull($product->category_id);
        $this->assertNull($product->subcategory_id);
        $this->assertSame(0, $product->categories()->count());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'last_update_summary' => 'Category assignment removed because its category tree was deleted.',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItemId,
            'category_id' => null,
            'is_active' => false,
        ]);
    }

    public function test_only_categories_without_children_are_returned_as_product_destinations(): void
    {
        $root = $this->category('Sportswear');
        $middle = $this->category('Uniforms', $root);
        $deepLeaf = $this->category('Basketball Uniforms', $middle);
        $directLeaf = $this->category('Headwear');
        app(CategoryTreeService::class)->rebuildClosure();

        $leafOptions = app(CategoryTreeService::class)->leafOptions();
        $leafIds = $leafOptions->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $this->assertNotContains($root->id, $leafIds);
        $this->assertNotContains($middle->id, $leafIds);
        $this->assertContains($deepLeaf->id, $leafIds);
        $this->assertContains($directLeaf->id, $leafIds);
        $this->assertFalse($root->fresh()->isLeaf());
        $this->assertFalse($middle->fresh()->isLeaf());
        $this->assertTrue($deepLeaf->fresh()->isLeaf());
        $this->assertTrue($directLeaf->fresh()->isLeaf());
    }

    public function test_turning_a_leaf_into_a_parent_removes_its_direct_product_assignments(): void
    {
        $formerLeaf = $this->category('Warmups');
        $otherLeaf = $this->category('Accessories');
        app(CategoryTreeService::class)->rebuildClosure();
        $product = $this->product('Team Warmup Jacket', $formerLeaf);
        $product->categories()->attach([
            $formerLeaf->id => ['is_primary' => true, 'is_featured' => false, 'sort_order' => 0],
            $otherLeaf->id => ['is_primary' => false, 'is_featured' => false, 'sort_order' => 1],
        ]);

        $this->category('Youth Warmups', $formerLeaf);
        app(CategoryTreeService::class)->rebuildClosure();

        $normalized = app(LeafCategoryAssignmentService::class)->enforce();

        $this->assertSame(1, $normalized);
        $this->assertDatabaseMissing('category_product', [
            'product_id' => $product->id,
            'category_id' => $formerLeaf->id,
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $otherLeaf->id,
            'is_primary' => true,
        ]);

        $product->refresh();
        $this->assertSame($otherLeaf->id, $product->category_id);
        $this->assertNull($product->subcategory_id);
    }

    public function test_hierarchy_normalization_clears_legacy_only_parent_assignments(): void
    {
        $formerLeaf = $this->category('Legacy Teamwear');
        app(CategoryTreeService::class)->rebuildClosure();
        $product = $this->product('Legacy Assigned Product', $formerLeaf);

        $this->category('Legacy Teamwear Child', $formerLeaf);
        app(CategoryTreeService::class)->rebuildClosure();

        $normalized = app(LeafCategoryAssignmentService::class)->enforce();

        $this->assertSame(1, $normalized);
        $product->refresh();
        $this->assertNull($product->category_id);
        $this->assertNull($product->subcategory_id);
        $this->assertSame(0, $product->categories()->count());
    }

    public function test_category_product_endpoint_rejects_parent_assignment_and_accepts_leaf_assignment(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $root = $this->category('Apparel');
        $leaf = $this->category('Training Vests', $root);
        app(CategoryTreeService::class)->rebuildClosure();
        $product = $this->product('Training Vest');

        $this->actingAs($admin, 'admin')
            ->from(route('admin.categories.products.index', $root))
            ->put(route('admin.categories.products.update', $root), [
                'assignment_action' => 'attach_products',
                'attach_product_ids' => [$product->id],
            ])
            ->assertRedirect(route('admin.categories.products.index', $root))
            ->assertSessionHasErrors('attach_product_ids');

        $this->assertDatabaseMissing('category_product', [
            'product_id' => $product->id,
            'category_id' => $root->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.categories.products.index', $leaf))
            ->put(route('admin.categories.products.update', $leaf), [
                'assignment_action' => 'attach_products',
                'attach_product_ids' => [$product->id],
            ])
            ->assertRedirect(route('admin.categories.products.index', $leaf))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $leaf->id,
            'is_primary' => true,
        ]);

        $product->refresh();
        $this->assertSame($root->id, $product->category_id);
        $this->assertSame($leaf->id, $product->subcategory_id);
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

    private function product(string $name, ?Category $root = null, ?Category $leaf = null): Product
    {
        return Product::query()->create([
            'category_id' => $root?->id,
            'subcategory_id' => $leaf?->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'sku' => 'TEST-'.Str::upper(Str::random(10)),
            'status' => 'active',
            'base_price' => 10,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_active' => true,
        ]);
    }
}
