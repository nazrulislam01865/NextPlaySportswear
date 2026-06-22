<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Contracts\View\View;
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
        $products = $this->productCatalogService->search($query);

        return view('storefront.products.index', [
            'products' => $products,
            'query' => $query,
            'seo' => [
                'title' => filled($query)
                    ? 'Search results for '.$query.' | '.config('storefront.name')
                    : 'Products | '.config('storefront.name'),
                'description' => 'Browse custom jerseys, uniforms, hoodies, caps, bags, and bulk-ready team sportswear products.',
                'canonical' => route('products.index'),
            ],
        ]);
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
