<?php

namespace Tests\Feature;

use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StorefrontCatalogFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_all_products_filters_work_together_without_shipping_or_production_facets(): void
    {
        $apparel = $this->createCategory('Apparel', 'apparel');
        $soccer = $this->createCategory('Soccer', 'soccer', 'sport');

        $jersey = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Custom Red Soccer Jersey',
            'slug' => 'custom-red-soccer-jersey',
            'sku' => 'FILTER-JERSEY-001',
            'product_type' => 'Jersey',
            'base_price' => 99,
            'minimum_quantity' => 12,
            'is_customizable' => true,
            'artwork_upload_enabled' => true,
            'jersey_roster_enabled' => true,
            'track_inventory' => false,
            'rating_average' => 4.7,
            'reviews_count' => 12,
        ]);
        $jersey->categories()->attach([
            $apparel->id => ['is_primary' => true, 'sort_order' => 0],
            $soccer->id => ['is_primary' => false, 'sort_order' => 1],
        ]);

        $sizeGroup = $jersey->sizeGroups()->create([
            'name' => 'Adult Sizes',
            'code' => 'adult',
            'is_active' => true,
        ]);
        $sizeGroup->sizes()->create(['label' => 'M', 'code' => 'm', 'is_active' => true]);

        $jersey->artworkMethods()->create([
            'name' => 'Sublimation',
            'code' => 'sublimation',
            'is_active' => true,
        ]);

        $jersey->priceTiers()->createMany([
            ['label' => 'Small team', 'minimum_quantity' => 1, 'maximum_quantity' => 11, 'unit_price' => 30, 'sort_order' => 0],
            ['label' => 'Team price', 'minimum_quantity' => 12, 'maximum_quantity' => null, 'unit_price' => 22, 'sort_order' => 1],
        ]);

        $neckStyle = CatalogAttribute::query()->create([
            'name' => 'Neck Style',
            'slug' => 'neck-style',
            'display_type' => 'checkbox',
            'is_filterable' => true,
            'is_active' => true,
        ]);
        $vNeck = $neckStyle->values()->create(['label' => 'V-Neck', 'slug' => 'v-neck', 'is_active' => true]);

        foreach ([
            ['name' => 'Production Time', 'slug' => 'production-time', 'label' => 'Rush', 'value_slug' => 'rush'],
            ['name' => 'Shipping Time', 'slug' => 'shipping-time', 'label' => '3–5 days', 'value_slug' => '3-5-days'],
        ] as $blockedAttribute) {
            $attribute = CatalogAttribute::query()->create([
                'name' => $blockedAttribute['name'],
                'slug' => $blockedAttribute['slug'],
                'display_type' => 'checkbox',
                'is_filterable' => true,
                'is_active' => true,
            ]);
            $value = $attribute->values()->create([
                'label' => $blockedAttribute['label'],
                'slug' => $blockedAttribute['value_slug'],
                'is_active' => true,
            ]);
            $jersey->attributeValues()->attach($value->id, ['sort_order' => 0]);
        }
        $jersey->attributeValues()->attach($vNeck->id, ['sort_order' => 0]);

        $fabricGroup = $jersey->optionGroups()->create([
            'name' => 'Fabric',
            'code' => 'fabric',
            'section' => 'product',
            'type' => 'select',
            'jersey_customization_type' => 'fabric',
            'display_mode' => 'select',
            'show_in_summary' => true,
            'is_active' => true,
        ]);
        $fabricGroup->values()->create([
            'label' => 'Polyester',
            'code' => 'polyester',
            'is_active' => true,
        ]);

        $colorGroup = $jersey->optionGroups()->create([
            'name' => 'Primary Color',
            'code' => 'primary-color',
            'section' => 'product',
            'type' => 'swatch',
            'jersey_customization_type' => 'color',
            'display_mode' => 'select',
            'show_in_summary' => true,
            'is_active' => true,
        ]);
        $colorGroup->values()->create([
            'label' => 'Red',
            'code' => 'red',
            'color_hex' => '#D81E35',
            'is_active' => true,
        ]);

        $readyMade = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Ready Made Training Cap',
            'slug' => 'ready-made-training-cap',
            'sku' => 'FILTER-CAP-001',
            'product_type' => 'Cap',
            'base_price' => 9,
            'minimum_quantity' => 1,
            'is_customizable' => false,
            'track_inventory' => true,
            'stock_quantity' => 25,
        ]);
        $readyMade->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);

        $response = $this->get(route('products.index', [
            'categories' => [$apparel->id],
            'sports' => [$soccer->id],
            'product_types' => ['Jersey'],
            'colors' => ['red'],
            'materials' => ['polyester'],
            'artwork_methods' => ['sublimation'],
            'moq' => ['12-24'],
            'customization' => ['customizable', 'artwork-upload', 'player-details'],
            'availability' => ['made-to-order'],
            'min_rating' => 4,
            'min_price' => 20,
            'max_price' => 25,
            'sort' => 'rating-high',
        ]));

        $response
            ->assertOk()
            ->assertSee('Custom Red Soccer Jersey')
            ->assertDontSee('Ready Made Training Cap')
            ->assertSee('Fabric / material')
            ->assertSee('Decoration / branding')
            ->assertSee('Neck Style')
            ->assertDontSee('Audience / size group')
            ->assertDontSee('np-catalog-size-grid', false)
            ->assertDontSee('Need Bulk Quote?')
            ->assertDontSee('Production Time')
            ->assertDontSee('Shipping Time');
    }

    public function test_category_pages_use_the_same_shared_filters(): void
    {
        $apparel = $this->createCategory('Performance Apparel', 'performance-apparel');

        $jersey = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Made To Order Team Jersey',
            'slug' => 'made-to-order-team-jersey',
            'sku' => 'CATEGORY-FILTER-001',
            'product_type' => 'Jersey',
            'minimum_quantity' => 12,
            'is_customizable' => true,
            'track_inventory' => false,
        ]);
        $jersey->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);

        $cap = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'In Stock Team Cap',
            'slug' => 'in-stock-team-cap',
            'sku' => 'CATEGORY-FILTER-002',
            'product_type' => 'Cap',
            'minimum_quantity' => 1,
            'is_customizable' => false,
            'track_inventory' => true,
            'stock_quantity' => 20,
        ]);
        $cap->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);

        $response = $this->get(route('categories.show', [
            'slug' => $apparel->slug,
            'product_types' => ['Jersey'],
            'moq' => ['12-24'],
            'customization' => ['customizable'],
            'availability' => ['made-to-order'],
        ]));

        $response
            ->assertOk()
            ->assertSee('Made To Order Team Jersey')
            ->assertDontSee('In Stock Team Cap')
            ->assertSee('Product type')
            ->assertSee('Minimum order quantity')
            ->assertDontSee('Audience / size group')
            ->assertDontSee('np-catalog-size-grid', false)
            ->assertDontSee('Production Time')
            ->assertDontSee('Shipping Time');
    }

    private function createCategory(string $name, string $slug, string $type = 'standard'): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'display_type' => $type === 'sport' ? 'sport' : 'collection',
            'category_type' => $type,
            'description' => $name.' products',
            'image_url' => 'https://example.com/'.$slug.'.jpg',
            'image_alt' => $name,
            'status' => 'active',
            'is_active' => true,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createProduct(array $overrides): Product
    {
        return Product::query()->create(array_merge([
            'name' => 'Catalog Product',
            'slug' => 'catalog-product-'.uniqid(),
            'sku' => 'CAT-'.strtoupper(uniqid()),
            'status' => 'active',
            'short_description' => 'Filter test product.',
            'base_price' => 15,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_featured' => false,
            'is_customizable' => true,
            'is_active' => true,
            'track_inventory' => false,
            'stock_quantity' => 0,
            'allow_backorder' => false,
            'artwork_upload_enabled' => false,
            'jersey_roster_enabled' => false,
            'published_at' => now()->subMinute(),
        ], $overrides));
    }
}
