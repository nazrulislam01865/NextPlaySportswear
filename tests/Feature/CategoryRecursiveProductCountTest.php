<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryProductCountService;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryRecursiveProductCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_parent_aggregates_unique_products_from_every_descendant_level(): void
    {
        $root = $this->category('Apparel');
        $middle = $this->category('Training Wear', $root);
        $vests = $this->category('Training Vests', $middle);
        $jackets = $this->category('Training Jackets', $middle);
        $hoodies = $this->category('Hoodies', $root);
        $emptyLeaf = $this->category('Empty Leaf', $middle);

        $vestProduct = $this->product('Training Vest');
        $jacketProduct = $this->product('Training Jacket');
        $hoodieProduct = $this->product('Team Hoodie');
        $sharedProduct = $this->product('Shared Training Product');
        $legacyProduct = $this->product('Legacy Vest', $root, $vests);

        $vestProduct->categories()->attach($vests->id, $this->pivot(true, 0));
        $jacketProduct->categories()->attach($jackets->id, $this->pivot(true, 0));
        $hoodieProduct->categories()->attach($hoodies->id, $this->pivot(true, 0));
        $sharedProduct->categories()->attach([
            $vests->id => $this->pivot(true, 0),
            $jackets->id => $this->pivot(false, 1),
        ]);

        // No closure rebuild is performed here. Recursive admin counts must still
        // remain correct by following the authoritative parent_id hierarchy.
        $counts = app(CategoryProductCountService::class)->allCounts();
        $descendantIds = app(CategoryTreeService::class)->descendantIds($root->id, true);

        $this->assertContains($root->id, $descendantIds);
        $this->assertContains($middle->id, $descendantIds);
        $this->assertContains($vests->id, $descendantIds);
        $this->assertContains($jackets->id, $descendantIds);
        $this->assertContains($hoodies->id, $descendantIds);
        $this->assertSame(3, $counts[$vests->id]);
        $this->assertSame(2, $counts[$jackets->id]);
        $this->assertSame(0, $counts[$emptyLeaf->id]);
        $this->assertSame(4, $counts[$middle->id]);
        $this->assertSame(1, $counts[$hoodies->id]);
        $this->assertSame(5, $counts[$root->id]);
    }

    public function test_soft_deleted_products_are_not_included_in_recursive_totals(): void
    {
        $root = $this->category('Accessories');
        $leaf = $this->category('Bags', $root);
        $activeProduct = $this->product('Active Bag');
        $deletedProduct = $this->product('Deleted Bag');

        $activeProduct->categories()->attach($leaf->id, $this->pivot(true, 0));
        $deletedProduct->categories()->attach($leaf->id, $this->pivot(true, 0));
        $deletedProduct->delete();

        $counts = app(CategoryProductCountService::class)->allCounts();

        $this->assertSame(1, $counts[$leaf->id]);
        $this->assertSame(1, $counts[$root->id]);
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
            'sku' => 'COUNT-'.Str::upper(Str::random(10)),
            'status' => 'active',
            'base_price' => 10,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_active' => true,
        ]);
    }

    /** @return array{is_primary:bool,is_featured:bool,sort_order:int} */
    private function pivot(bool $primary, int $sortOrder): array
    {
        return [
            'is_primary' => $primary,
            'is_featured' => false,
            'sort_order' => $sortOrder,
        ];
    }
}
