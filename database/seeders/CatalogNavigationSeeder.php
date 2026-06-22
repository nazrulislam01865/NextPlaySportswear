<?php

namespace Database\Seeders;

use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Product;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\NavigationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogNavigationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->normalizeCategoryHierarchy();
            app(CategoryTreeService::class)->rebuildClosure();
            $this->syncProductCategories();
            $attributes = $this->seedAttributes();
            $this->assignProductAttributes($attributes);
            $this->assignCategoryFilters($attributes);
            $this->seedMenus();
        });

        app(NavigationService::class)->flushCache();
    }

    private function normalizeCategoryHierarchy(): void
    {
        $apparel = Category::query()->where('slug', 'apparel')->first();
        $accessories = Category::query()->where('slug', 'accessories')->first();

        foreach (['hoodies-sweatshirts', 'performance-t-shirts', 'training-wear', 'outerwear-jackets'] as $slug) {
            if ($apparel) {
                Category::query()->where('slug', $slug)->whereNull('parent_id')->update(['parent_id' => $apparel->id]);
            }
        }
        foreach (['caps-headwear', 'sports-bags', 'fan-gear', 'promotional-products'] as $slug) {
            if ($accessories) {
                Category::query()->where('slug', $slug)->whereNull('parent_id')->update(['parent_id' => $accessories->id]);
            }
        }

        Category::query()->whereIn('slug', ['football', 'baseball', 'basketball', 'soccer', 'softball', 'volleyball'])
            ->update(['category_type' => 'sport']);
        Category::query()->where('display_type', 'sport')->update(['category_type' => 'sport']);
        Category::query()->where('is_active', true)->update(['status' => 'active', 'is_visible_in_catalog' => true]);
    }

    private function syncProductCategories(): void
    {
        Product::query()->with('categories')->chunkById(100, function ($products): void {
            foreach ($products as $product) {
                $legacyIds = collect([$product->category_id, $product->subcategory_id])
                    ->filter()->map(fn ($id) => (int) $id)->unique()->values();
                $existingPrimary = $product->categories->first(fn ($category) => (bool) $category->pivot->is_primary);
                $primaryId = $existingPrimary?->id ?: $product->subcategory_id ?: $product->category_id ?: $product->categories->first()?->id;

                $existingIds = $product->categories->pluck('id')->map(fn ($id) => (int) $id);
                $missingIds = $legacyIds->diff($existingIds)->values();

                if ($missingIds->isNotEmpty()) {
                    $product->categories()->attach($missingIds->mapWithKeys(fn (int $id, int $index) => [
                        $id => [
                            'is_primary' => false,
                            'is_featured' => false,
                            'sort_order' => (int) $product->sort_order + $existingIds->count() + $index,
                        ],
                    ])->all());
                }

                // Seeders are intentionally non-destructive: preserve existing category
                // merchandising metadata and only establish a primary assignment when
                // the product does not already have one.
                if ($primaryId && ! DB::table('category_product')->where('product_id', $product->id)->where('is_primary', true)->exists()) {
                    DB::table('category_product')
                        ->where('product_id', $product->id)
                        ->where('category_id', $primaryId)
                        ->update(['is_primary' => true, 'updated_at' => now()]);
                }
            }
        });
    }

    /** @return array<string, CatalogAttribute> */
    private function seedAttributes(): array
    {
        $definitions = [
            'color' => ['Color Family', 'color', null, [
                ['Navy', '#15345D'], ['Royal Blue', '#1756A9'], ['Red', '#D81E35'], ['Black', '#17191C'], ['White', '#FFFFFF'], ['Green', '#146B49'],
            ]],
            'size' => ['Product Size', 'checkbox', null, [['Adult', null], ['Women', null], ['Youth', null]]],
            'production-time' => ['Production Time', 'checkbox', 'days', [['Standard', null], ['Priority', null], ['Rush', null]]],
            'shipping-time' => ['Shipping Time', 'checkbox', 'business days', [['3–5 Business Days', null], ['5–8 Business Days', null], ['8–12 Business Days', null]]],
            'free-shipping' => ['Free Shipping', 'radio', null, [['Available', null], ['Not Included', null]]],
            'free-setup' => ['Free Setup', 'radio', null, [['Included', null], ['Not Included', null]]],
            'brand' => ['Brand', 'checkbox', null, [['NextPlay Sportswear', null]]],
        ];

        $attributes = [];
        foreach ($definitions as $slug => [$name, $type, $unit, $values]) {
            $attribute = CatalogAttribute::query()->firstOrCreate(['slug' => $slug], [
                'name' => $name, 'display_type' => $type, 'unit' => $unit,
                'is_filterable' => true, 'is_searchable' => in_array($slug, ['color', 'brand'], true),
                'is_active' => true, 'sort_order' => count($attributes) * 10,
            ]);
            foreach ($values as $sort => [$label, $hex]) {
                $attribute->values()->firstOrCreate(['slug' => Str::slug($label)], [
                    'label' => $label, 'color_hex' => $hex, 'is_active' => true, 'sort_order' => $sort,
                ]);
            }
            $attributes[$slug] = $attribute->load('values');
        }

        return $attributes;
    }

    private function assignProductAttributes(array $attributes): void
    {
        Product::query()->with(['optionGroups.values', 'sizeGroups', 'productionSpeeds'])->chunkById(100, function ($products) use ($attributes): void {
            foreach ($products as $product) {
                $valueIds = collect();
                $colorMap = $attributes['color']->values->keyBy('slug');
                foreach ($product->optionGroups->where('code', 'primary-color')->flatMap->values as $option) {
                    $slug = Str::slug($option->label);
                    $match = $colorMap->first(fn ($value) => Str::contains($slug, $value->slug) || Str::contains($value->slug, $slug));
                    if ($match) $valueIds->push($match->id);
                }
                $sizeMap = $attributes['size']->values->keyBy('slug');
                foreach ($product->sizeGroups as $group) {
                    $name = Str::lower($group->name);
                    foreach (['adult', 'women', 'youth'] as $key) if (Str::contains($name, $key) && $sizeMap->has($key)) $valueIds->push($sizeMap[$key]->id);
                }
                $productionMap = $attributes['production-time']->values->keyBy('slug');
                foreach ($product->productionSpeeds as $speed) {
                    foreach (['standard', 'priority', 'rush'] as $key) if (Str::contains(Str::lower($speed->name), $key) && $productionMap->has($key)) $valueIds->push($productionMap[$key]->id);
                }
                $valueIds->push($attributes['brand']->values->first()->id);
                $valueIds->push($attributes['free-setup']->values->first()->id);
                $valueIds->push($attributes['shipping-time']->values->first()->id);
                $valueIds->push($attributes['free-shipping']->values->first()->id);
                $product->attributeValues()->syncWithoutDetaching($valueIds->filter()->unique()->values()->all());
            }
        });
    }

    private function assignCategoryFilters(array $attributes): void
    {
        $defaultSlugs = ['color', 'size', 'brand', 'production-time', 'shipping-time', 'free-shipping', 'free-setup'];
        Category::query()->where('status', 'active')->chunkById(100, function ($categories) use ($attributes, $defaultSlugs): void {
            foreach ($categories as $category) {
                if ($category->filters()->exists()) {
                    continue;
                }
                $category->filters()->attach(collect($defaultSlugs)->mapWithKeys(fn (string $slug, int $index) => [
                    $attributes[$slug]->id => ['label' => null, 'is_expanded' => $index < 4, 'sort_order' => $index * 10],
                ])->all());
            }
        });
    }

    private function seedMenus(): void
    {
        $header = $this->freshMenu('Primary Header', 'header-primary');
        if (! $header->allItems()->exists()) {
            $this->menuItem($header, null, 'Home', 'route', routeName: 'home', sort: 0);
            $shop = $this->menuItem($header, null, 'Shop Products', 'route', routeName: 'categories.index', sort: 10);
            $this->categoryChildren($header, $shop->id, Category::query()->whereNull('parent_id')->storefrontVisible()->ordered()->get(), 0);
            $this->menuItem($header, null, 'All Products', 'route', routeName: 'products.index', sort: 20);
            $this->menuItem($header, null, 'How It Works', 'route', routeName: 'how-to-order', sort: 30);
            $this->menuItem($header, null, 'Bulk Quote', 'route', routeName: 'quote.request', sort: 40, cssClass: 'text-brand-red');
        }

        $this->seedFooterMenu('Footer Shop', 'footer-shop', ['team-uniforms', 'custom-jerseys', 'apparel', 'accessories', 'promotional-products']);
        $this->seedFooterMenu('Footer Sports', 'footer-sports', ['football-jerseys', 'baseball-uniforms', 'basketball-jerseys', 'soccer-kits']);
        $this->seedRouteFooter('Footer Support', 'footer-support', [
            ['Help Center', 'faq'], ['How to Order', 'how-to-order'], ['Track Order', 'orders.track'], ['Size Guide', 'size-guide'], ['Contact Us', 'contact'],
        ]);
        $this->seedRouteFooter('Footer Company', 'footer-company', [
            ['About Us', 'about'], ['Shipping & Delivery', 'shipping'], ['Returns & Refunds', 'returns'], ['Payment Information', 'payment-information'],
        ]);
    }

    private function freshMenu(string $name, string $location): Menu
    {
        return Menu::query()->firstOrCreate(['location' => $location], ['name' => $name, 'slug' => Str::slug($name), 'is_active' => true]);
    }

    private function categoryChildren(Menu $menu, int $parentId, $categories, int $depth): void
    {
        if ($depth >= 4) return;
        foreach ($categories as $sort => $category) {
            $item = $this->menuItem($menu, $parentId, $category->displayLabel(), 'category', categoryId: $category->id, sort: $sort);
            $children = Category::query()->where('parent_id', $category->id)->storefrontVisible()->ordered()->get();
            if ($children->isNotEmpty()) $this->categoryChildren($menu, $item->id, $children, $depth + 1);
        }
    }

    private function seedFooterMenu(string $name, string $location, array $slugs): void
    {
        $menu = $this->freshMenu($name, $location);
        if ($menu->allItems()->exists()) return;
        foreach (Category::query()->whereIn('slug', $slugs)->get()->sortBy(fn ($category) => array_search($category->slug, $slugs, true)) as $sort => $category) {
            $this->menuItem($menu, null, $category->displayLabel(), 'category', categoryId: $category->id, sort: (int) $sort);
        }
    }

    private function seedRouteFooter(string $name, string $location, array $items): void
    {
        $menu = $this->freshMenu($name, $location);
        if ($menu->allItems()->exists()) return;
        foreach ($items as $sort => [$label, $route]) $this->menuItem($menu, null, $label, 'route', routeName: $route, sort: $sort);
    }

    private function menuItem(Menu $menu, ?int $parentId, string $label, string $type, ?int $categoryId = null, ?string $routeName = null, int $sort = 0, ?string $cssClass = null)
    {
        return $menu->allItems()->create([
            'parent_id' => $parentId, 'label' => $label, 'link_type' => $type,
            'category_id' => $categoryId, 'route_name' => $routeName, 'target' => '_self',
            'css_class' => $cssClass, 'is_active' => true, 'sort_order' => $sort,
        ]);
    }
}
