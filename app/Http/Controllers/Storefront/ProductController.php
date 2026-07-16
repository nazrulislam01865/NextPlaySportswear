<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $productCatalogService
    ) {
    }

    public function index(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();
        $tag = trim((string) $request->query('tag', ''));
        $selectedCategoryIds = $this->productCatalogService->normalizeCategoryFilterIds((array) $request->query('categories', []));
        $products = $this->productCatalogService->searchPaginated($query, $tag, $selectedCategoryIds);
        $categoryFilters = $this->productCatalogService->categoryFilterTree($selectedCategoryIds);
        $hasFilters = filled($query) || filled($tag) || $selectedCategoryIds !== [];

        return view('storefront.products.index', [
            'products' => $products,
            'query' => $query,
            'tag' => $tag,
            'selectedCategoryIds' => $selectedCategoryIds,
            'categoryFilters' => $categoryFilters,
            'hasFilters' => $hasFilters,
            'seo' => [
                'title' => filled($tag)
                    ? 'Products tagged '.$tag.' | '.config('storefront.name')
                    : (filled($query)
                        ? 'Search results for '.$query.' | '.config('storefront.name')
                        : 'Products | '.config('storefront.name')),
                'description' => 'Browse custom jerseys, uniforms, hoodies, caps, bags, and bulk-ready team sportswear products.',
                'canonical' => route('products.index'),
            ],
        ]);
    }


    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->limit(100)->toString();

        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $suggestions = collect($this->productCatalogService->suggestions($query, 8))
            ->map(fn (array $product): array => [
                'title' => (string) ($product['title'] ?? ''),
                'sku' => (string) ($product['sku'] ?? ''),
                'category' => (string) (($product['category'] ?? '') ?: ($product['sport'] ?? '')),
                'price' => (string) ($product['price'] ?? ''),
                'image' => (string) (($product['image'] ?? '') ?: asset('images/product-placeholder.svg')),
                'url' => (string) (($product['url'] ?? '') ?: route('products.show', $product['slug'] ?? '')),
            ])
            ->values();

        return response()->json(['data' => $suggestions]);
    }

    public function show(string $slug): View
    {
        $product = $this->productCatalogService->findBySlug($slug);

        abort_if(! $product, 404);

        $priceValues = collect($product['price_tiers'] ?? [])->pluck('unit')->filter();
        $structuredData = [[
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['title'],
            'description' => $product['summary'],
            'sku' => $product['sku'],
            'image' => collect($product['gallery'])->pluck('url')->values()->all(),
            'brand' => ['@type' => 'Brand', 'name' => $product['brand'] ?: config('storefront.name')],
            'category' => trim(implode(' > ', array_filter([$product['category'] ?? null, $product['subcategory'] ?? null]))),
            'offers' => [
                '@type' => 'AggregateOffer',
                'url' => $product['url'],
                'priceCurrency' => $product['currency'] ?? 'USD',
                'lowPrice' => $priceValues->min() ?? $product['base_price'],
                'highPrice' => $priceValues->max() ?? $product['base_price'],
                'offerCount' => max(1, $priceValues->count()),
                'availability' => ($product['track_inventory'] ?? false) && ($product['stock_quantity'] ?? 0) <= 0 && ! ($product['allow_backorder'] ?? false)
                    ? 'https://schema.org/OutOfStock'
                    : 'https://schema.org/InStock',
            ],
        ]];

        if (! empty($product['custom_schema']) && is_array($product['custom_schema'])) {
            $structuredData[] = $product['custom_schema'];
        }

        return view('storefront.products.show', [
            'product' => $product,
            'relatedProducts' => $this->productCatalogService->relatedFor($product),
            'seo' => [
                'title' => $product['meta_title'] ?: $product['title'].' | '.config('storefront.name'),
                'description' => $product['meta_description'] ?: $product['summary'],
                'robots' => $product['robots'] ?? 'index, follow',
                'canonical' => $product['canonical_url'] ?: $product['url'],
                'og_title' => $product['og_title'] ?: $product['title'].' | '.config('storefront.name'),
                'og_description' => $product['og_description'] ?: $product['summary'],
                'og_image' => $product['og_image'] ?: $product['image'],
                'og_type' => 'product',
                'schema_type' => 'ItemPage',
            ],
            'structuredData' => $structuredData,
        ]);
    }
}
