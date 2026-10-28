<?php

namespace Tests\Feature;

use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Gender;
use App\Models\PersonalizationTemplate;
use App\Services\Storefront\PersonalizationFieldFacets;
use App\Services\Storefront\ProductCatalogService;
use App\Services\Catalog\ProductOptionFilterSyncService;
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

        $playerTemplate = PersonalizationTemplate::query()->create([
            'category_id' => $apparel->id,
            'name' => 'INTERNAL PLAYER DETAILS TEMPLATE',
            'section_title' => 'Player customization',
            'is_active' => true,
            'is_published' => true,
            'fields' => [
                ['key' => 'name', 'label' => 'Player Name', 'type' => 'text', 'enabled' => true],
                ['key' => 'number', 'label' => 'Player Number', 'type' => 'number', 'enabled' => true],
            ],
        ]);
        $jersey->personalizationTemplates()->attach($playerTemplate->id, ['sort_order' => 0]);

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

        // The storefront Color facet now requires a manually assigned Master
        // Data > Attributes > Color value; option-group swatches do not count.
        $masterColor = CatalogAttribute::query()->create([
            'name' => 'Color', 'slug' => 'color', 'display_type' => 'color',
            'is_filterable' => true, 'is_active' => true,
        ]);
        $masterRed = $masterColor->values()->create([
            'label' => 'Red', 'slug' => 'red', 'color_hex' => '#D81E35', 'is_active' => true,
        ]);
        $jersey->attributeValues()->attach($masterRed->id, ['sort_order' => 0]);

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
            'customization' => ['customizable', 'artwork-upload', PersonalizationFieldFacets::valueForLabel('Player Name')],
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

    public function test_all_products_gender_filter_uses_active_gender_master_data_and_combines_with_existing_filters(): void
    {
        $apparel = $this->createCategory('Gender Filter Apparel', 'gender-filter-apparel');
        $men = Gender::query()->where('slug', 'men')->firstOrFail();
        $women = Gender::query()->where('slug', 'women')->firstOrFail();
        $inactive = Gender::query()->create([
            'name' => 'Legacy Audience',
            'slug' => 'legacy-audience',
            'is_active' => false,
            'sort_order' => 999,
        ]);

        $mensJersey = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Mens Match Jersey',
            'slug' => 'mens-match-jersey',
            'sku' => 'GENDER-MEN-001',
            'product_type' => 'Jersey',
            'gender_id' => $men->id,
        ]);
        $mensJersey->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);

        $womensJersey = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Womens Match Jersey',
            'slug' => 'womens-match-jersey',
            'sku' => 'GENDER-WOMEN-001',
            'product_type' => 'Jersey',
            'gender_id' => $women->id,
        ]);
        $womensJersey->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);

        $mensCap = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Mens Training Cap',
            'slug' => 'mens-training-cap',
            'sku' => 'GENDER-MEN-002',
            'product_type' => 'Cap',
            'gender_id' => $men->id,
        ]);
        $mensCap->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);

        $inactiveProduct = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Neutral Training Top',
            'slug' => 'neutral-training-top',
            'sku' => 'GENDER-LEGACY-001',
            'product_type' => 'Jersey',
            'gender_id' => $inactive->id,
        ]);
        $inactiveProduct->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);

        $this->get(route('products.index', [
            'genders' => [$men->id],
            'product_types' => ['Jersey'],
        ]))
            ->assertOk()
            ->assertSee('Gender')
            ->assertSee('Men')
            ->assertSee('Women')
            ->assertSee('Kids')
            ->assertSee('Unisex')
            ->assertDontSee('Legacy Audience')
            ->assertSee('Mens Match Jersey')
            ->assertDontSee('Womens Match Jersey')
            ->assertDontSee('Mens Training Cap')
            ->assertDontSee('Neutral Training Top');

        $this->get(route('products.index', ['genders' => [$men->id, $women->id]]))
            ->assertOk()
            ->assertSee('Mens Match Jersey')
            ->assertSee('Womens Match Jersey')
            ->assertSee('Mens Training Cap')
            ->assertDontSee('Neutral Training Top');
    }

    public function test_all_products_price_sort_uses_the_same_discounted_price_shown_on_cards(): void
    {
        $apparel = $this->createCategory('Price Sort Apparel', 'price-sort-apparel');

        $regular = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Regular Price Product',
            'slug' => 'regular-price-product',
            'sku' => 'PRICE-SORT-001',
            'base_price' => 4.05,
        ]);
        $regular->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);
        $regular->priceTiers()->create([
            'label' => '100+',
            'minimum_quantity' => 100,
            'maximum_quantity' => null,
            'unit_price' => 4.05,
            'compare_at_price' => null,
            'sort_order' => 0,
        ]);

        $discounted = $this->createProduct([
            'category_id' => $apparel->id,
            'name' => 'Discounted Price Product',
            'slug' => 'discounted-price-product',
            'sku' => 'PRICE-SORT-002',
            'base_price' => 5.25,
            'compare_at_price' => 3.56,
        ]);
        $discounted->categories()->attach($apparel->id, ['is_primary' => true, 'sort_order' => 0]);
        $discounted->priceTiers()->create([
            'label' => '100+',
            'minimum_quantity' => 100,
            'maximum_quantity' => null,
            'unit_price' => 5.25,
            'compare_at_price' => null,
            'sort_order' => 0,
        ]);

        $this->get(route('products.index', ['sort' => 'price-low']))
            ->assertOk()
            ->assertSeeInOrder(['Discounted Price Product', 'Regular Price Product'])
            ->assertSee('From $3.56')
            ->assertSee('From $4.05');

        $this->get(route('products.index', ['sort' => 'price-high']))
            ->assertOk()
            ->assertSeeInOrder(['Regular Price Product', 'Discounted Price Product']);
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

    public function test_personalization_facets_use_enabled_field_labels_not_template_names_and_exclude_availability(): void
    {
        $category = $this->createCategory('Personalized Caps', 'personalized-caps');
        $first = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Custom Cap With Name',
            'slug' => 'custom-cap-with-name',
            'jersey_roster_enabled' => true,
        ]);
        $second = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Custom Cap With Logo',
            'slug' => 'custom-cap-with-logo',
            'jersey_roster_enabled' => true,
        ]);
        $third = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Cap With Unpublished Template',
            'slug' => 'cap-with-unpublished-template',
            'jersey_roster_enabled' => true,
        ]);
        foreach ([$first, $second, $third] as $product) {
            $product->categories()->attach($category->id, ['is_primary' => true, 'sort_order' => 0]);
        }

        $firstTemplate = PersonalizationTemplate::query()->create([
            'category_id' => $category->id,
            'name' => 'ADMIN ONLY TEMPLATE A',
            'section_title' => 'Internal heading A',
            'is_active' => true,
            'is_published' => true,
            'fields' => [
                ['key' => 'name', 'label' => 'Printed Name', 'type' => 'text', 'enabled' => true],
                ['key' => 'number', 'label' => 'Jersey Number', 'type' => 'number', 'enabled' => true],
                ['key' => 'disabled', 'label' => 'Never Visible', 'type' => 'text', 'enabled' => false],
            ],
        ]);
        $secondTemplate = PersonalizationTemplate::query()->create([
            'category_id' => $category->id,
            'name' => 'ADMIN ONLY TEMPLATE B',
            'section_title' => 'Internal heading B',
            'is_active' => true,
            'is_published' => true,
            'fields' => [
                ['key' => 'another_name', 'label' => 'Printed Name', 'type' => 'text', 'enabled' => true],
                ['key' => 'logo', 'label' => 'Logo Image', 'type' => 'image', 'enabled' => true],
            ],
        ]);
        $unpublished = PersonalizationTemplate::query()->create([
            'category_id' => $category->id,
            'name' => 'DRAFT INTERNAL TEMPLATE',
            'section_title' => 'Do not expose',
            'is_active' => true,
            'is_published' => false,
            'fields' => [['key' => 'draft', 'label' => 'Secret Draft Field', 'type' => 'text', 'enabled' => true]],
        ]);
        $first->personalizationTemplates()->attach($firstTemplate->id, ['sort_order' => 0]);
        $second->personalizationTemplates()->attach($secondTemplate->id, ['sort_order' => 0]);
        $third->personalizationTemplates()->attach($unpublished->id, ['sort_order' => 0]);

        $all = $this->get(route('products.index'));
        $all->assertOk()
            ->assertSee('Printed Name')
            ->assertSee('Jersey Number')
            ->assertSee('Logo Image')
            ->assertDontSee('Never Visible')
            ->assertDontSee('Secret Draft Field')
            ->assertDontSee('ADMIN ONLY TEMPLATE A')
            ->assertDontSee('ADMIN ONLY TEMPLATE B')
            ->assertDontSee('Roster fields available')
            ->assertDontSee('name="availability[]"', false);

        $categoryPage = $this->get(route('categories.show', ['slug' => $category->slug]));
        $categoryPage->assertOk()
            ->assertSee('Printed Name')
            ->assertDontSee('name="availability[]"', false)
            ->assertDontSee('ADMIN ONLY TEMPLATE A');

        // Only products with an assigned, published, enabled field match.
        $this->get(route('products.index', [
            'customization' => [PersonalizationFieldFacets::valueForLabel('Logo Image')],
        ]))->assertOk()
            ->assertSee('Custom Cap With Logo')
            ->assertDontSee('Custom Cap With Name')
            ->assertDontSee('Cap With Unpublished Template');

        $this->get(route('categories.show', [
            'slug' => $category->slug,
            'customization' => [PersonalizationFieldFacets::valueForLabel('Jersey Number')],
        ]))->assertOk()
            ->assertSee('Custom Cap With Name')
            ->assertDontSee('Custom Cap With Logo');

        // Old availability links no longer alter catalog results.
        $this->get(route('categories.show', [
            'slug' => $category->slug,
            'availability' => ['made-to-order'],
        ]))->assertOk()->assertSee('Custom Cap With Name')->assertSee('Custom Cap With Logo');
    }


    public function test_color_filter_requires_master_data_color_and_ignores_option_and_pantone_colors(): void
    {
        $category = $this->createCategory('Color Master Test', 'color-master-test');
        $master = CatalogAttribute::query()->create([
            'name' => 'Color', 'slug' => 'color', 'display_type' => 'color',
            'is_filterable' => true, 'is_active' => true,
        ]);
        $red = $master->values()->create([
            'label' => 'Master Red', 'slug' => 'master-red',
            'color_hex' => '#C00000', 'is_active' => true,
        ]);
        $inactive = $master->values()->create([
            'label' => 'Disabled Blue', 'slug' => 'disabled-blue',
            'color_hex' => '#0000FF', 'is_active' => false,
        ]);
        $pantone = CatalogAttribute::query()->create([
            'name' => 'Pantone Color', 'slug' => 'pantone-color', 'display_type' => 'color',
            'is_filterable' => true, 'is_active' => true,
        ]);
        $pantoneValue = $pantone->values()->create([
            'label' => 'Pantone Purple', 'slug' => 'pantone-purple',
            'color_hex' => '#661E7D', 'is_active' => true,
        ]);

        $manualProduct = $this->createProduct(['name' => 'Manual Master Color Product']);
        $manualProduct->categories()->attach($category->id);
        $manualProduct->attributeValues()->attach($red->id);
        $inactiveProduct = $this->createProduct(['name' => 'Inactive Master Color Product']);
        $inactiveProduct->categories()->attach($category->id);
        $inactiveProduct->attributeValues()->attach($inactive->id);
        $pantoneProduct = $this->createProduct(['name' => 'Pantone Only Product']);
        $pantoneProduct->categories()->attach($category->id);
        $pantoneProduct->attributeValues()->attach($pantoneValue->id);
        $optionProduct = $this->createProduct(['name' => 'Option Swatch Only Product']);
        $optionProduct->categories()->attach($category->id);
        $group = $optionProduct->optionGroups()->create([
            'name' => 'Primary Color', 'code' => 'primary-color',
            'section' => 'product', 'type' => 'swatch', 'display_mode' => 'select',
            'jersey_customization_type' => 'color', 'is_active' => true,
        ]);
        $group->values()->create([
            'label' => 'Option Yellow', 'code' => 'option-yellow',
            'color_hex' => '#EEEE00', 'is_active' => true,
        ]);

        $catalog = app(ProductCatalogService::class);
        $facets = $catalog->commonFilterOptions(Product::query()->published());
        $this->assertSame([[
            'value' => 'master-red', 'label' => 'Master Red',
            'color_hex' => '#C00000', 'count' => 1,
        ]], $facets['colors']);

        $filtered = $catalog->applyCommonCatalogFilters(
            Product::query()->published(), ['colors' => ['master-red']]
        )->pluck('products.id')->all();
        $this->assertSame([$manualProduct->id], $filtered);

        // Neither a Pantone attribute nor a product-option color is accepted
        // as a URL filter value, even when a user submits it manually.
        foreach (['pantone-purple', 'option-yellow', 'disabled-blue'] as $unavailableColor) {
            $this->assertSame([], $catalog->applyCommonCatalogFilters(
                Product::query()->published(), ['colors' => [$unavailableColor]]
            )->pluck('products.id')->all());
        }

        $this->get(route('categories.show', ['slug' => $category->slug]))
            ->assertOk()
            ->assertSee('Master Red')
            ->assertDontSee('Pantone Purple')
            ->assertDontSee('Option Yellow');
    }

    public function test_color_filter_is_hidden_when_no_master_color_is_assigned(): void
    {
        $category = $this->createCategory('Custom Colors Only', 'custom-colors-only');
        $product = $this->createProduct(['name' => 'Custom Swatches Product']);
        $product->categories()->attach($category->id);
        $group = $product->optionGroups()->create([
            'name' => 'Color', 'code' => 'color', 'section' => 'product',
            'type' => 'swatch', 'display_mode' => 'select', 'is_active' => true,
        ]);
        $group->values()->create([
            'label' => 'Bright Red', 'code' => 'bright-red',
            'color_hex' => '#FF0000', 'is_active' => true,
        ]);

        $this->assertSame([], app(ProductCatalogService::class)
            ->commonFilterOptions(Product::query()->published())['colors']);
        $this->get(route('products.index'))->assertOk()
            ->assertDontSee('name="colors[]"', false);
        $this->get(route('categories.show', ['slug' => $category->slug]))->assertOk()
            ->assertDontSee('name="colors[]"', false);
    }

    public function test_product_color_option_sync_does_not_overwrite_master_color_attribute(): void
    {
        $master = CatalogAttribute::query()->create([
            'name' => 'Color', 'slug' => 'color', 'display_type' => 'color',
            'is_filterable' => true, 'is_active' => true,
        ]);
        $master->values()->create(['label' => 'White', 'slug' => 'white', 'is_active' => true]);
        $product = $this->createProduct(['name' => 'Sync Guard Product']);
        $group = $product->optionGroups()->create([
            'name' => 'Color', 'code' => 'color', 'section' => 'product',
            'type' => 'swatch', 'display_mode' => 'select', 'use_as_filter' => true,
            'is_active' => true,
        ]);
        $group->values()->create(['label' => 'Temporary Pink', 'code' => 'temporary-pink',
            'color_hex' => '#FF50AA', 'is_active' => true]);

        app(ProductOptionFilterSyncService::class)->sync($product, []);

        $this->assertSame('Color', $master->fresh()->name);
        $this->assertSame(['white'], $master->fresh()->values->pluck('slug')->all());
        $this->assertNull($group->fresh()->catalog_attribute_id);
        $this->assertCount(0, $product->fresh()->attributeValues);
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
