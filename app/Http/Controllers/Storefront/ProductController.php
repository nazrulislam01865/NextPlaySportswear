<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Models\ProductWishlist;
use App\Services\Cart\CartService;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $productCatalogService,
        private readonly CartService $cart,
    ) {
    }

    public function index(ProductFilterRequest $request): View
    {
        $filters = $request->filters();
        $filters['categories'] = $this->productCatalogService->normalizeCategoryFilterIds($filters['categories']);
        $filters['sports'] = $this->productCatalogService->normalizeCategoryFilterIds($filters['sports']);

        $products = $this->productCatalogService->searchPaginated($filters);
        $filterOptions = $this->productCatalogService->filterOptions($filters);
        $hasFilters = $this->hasCatalogFilters($filters);

        return view('storefront.products.index', [
            'products' => $products,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'query' => $filters['q'],
            'tag' => $filters['tag'],
            'selectedCategoryIds' => $filters['categories'],
            'categoryFilters' => $filterOptions['categories'] ?? [],
            'hasFilters' => $hasFilters,
            'activeFilterCount' => $this->activeFilterCount($filters),
            'seo' => [
                'title' => filled($filters['tag'])
                    ? 'Products tagged '.$filters['tag'].' | '.config('storefront.name')
                    : (filled($filters['q'])
                        ? 'Search results for '.$filters['q'].' | '.config('storefront.name')
                        : 'Products | '.config('storefront.name')),
                'description' => 'Browse custom jerseys, uniforms, hoodies, caps, bags, and bulk-ready team sportswear products.',
                'canonical' => route('products.index'),
            ],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function hasCatalogFilters(array $filters): bool
    {
        return $this->activeFilterCount($filters) > 0;
    }

    /** @param array<string, mixed> $filters */
    private function activeFilterCount(array $filters): int
    {
        $count = filled($filters['q'] ?? null) ? 1 : 0;
        $count += filled($filters['tag'] ?? null) ? 1 : 0;

        foreach (['categories', 'sports', 'product_types', 'colors', 'materials', 'artwork_methods', 'moq', 'customization', 'availability'] as $key) {
            $count += count((array) ($filters[$key] ?? []));
        }

        foreach ((array) ($filters['attributes'] ?? []) as $values) {
            $count += count((array) $values);
        }

        $count += ($filters['min_price'] ?? null) !== null ? 1 : 0;
        $count += ($filters['max_price'] ?? null) !== null ? 1 : 0;
        $count += ($filters['min_rating'] ?? null) !== null ? 1 : 0;

        return $count;
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

    public function show(Request $request, string $slug): View
    {
        $product = $this->productCatalogService->findFullBySlug($slug);

        abort_if(! $product, 404);

        $cartEditItem = null;
        $cartItemKey = trim((string) $request->query('cart_item', ''));

        if ($cartItemKey !== '') {
            abort_unless(mb_strlen($cartItemKey) <= 64, 404);

            $cartEditItem = $this->cart->findItem($cartItemKey);

            abort_if($cartEditItem === null, 404, 'The cart item you are trying to edit no longer exists.');
            abort_unless(
                hash_equals((string) ($cartEditItem['product_slug'] ?? ''), (string) $product['slug']),
                404
            );
        }

        $priceValues = collect($product['price_tiers'] ?? [])->pluck('unit')->filter();
        $productSchema = [
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
        ];

        if (! empty($product['has_reviews']) && is_numeric($product['rating'] ?? null) && is_numeric($product['reviews_count'] ?? null)) {
            $productSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $product['rating'],
                'reviewCount' => (int) $product['reviews_count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        $schemaReviews = collect($product['review_items'] ?? [])
            ->filter(fn ($review): bool => is_array($review) && filled($review['body'] ?? null) && is_numeric($review['rating'] ?? null))
            ->take(12)
            ->map(function (array $review): array {
                $payload = [
                    '@type' => 'Review',
                    'reviewBody' => (string) $review['body'],
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (float) $review['rating'],
                        'bestRating' => 5,
                        'worstRating' => 1,
                    ],
                    'author' => [
                        '@type' => 'Person',
                        'name' => (string) ($review['author'] ?? 'Customer'),
                    ],
                ];

                if (filled($review['title'] ?? null)) {
                    $payload['name'] = (string) $review['title'];
                }

                if (filled($review['date'] ?? null)) {
                    $payload['datePublished'] = (string) $review['date'];
                }

                return $payload;
            })
            ->values()
            ->all();

        if ($schemaReviews !== []) {
            $productSchema['review'] = $schemaReviews;
        }

        $structuredData = [$productSchema];

        if (! empty($product['custom_schema']) && is_array($product['custom_schema'])) {
            $structuredData[] = $product['custom_schema'];
        }

        $customer = auth('web')->user();
        $productId = (int) ($product['id'] ?? 0);
        $isAuthenticatedCustomer = ($customer?->isCustomer() ?? false) && $productId > 0;
        $canonicalUrl = (string) (($product['canonical_url'] ?? null) ?: ($product['url'] ?? route('products.show', $product['slug'])));
        $firstGalleryImage = collect($product['gallery'] ?? [])->first();
        $productImage = (string) (($product['image'] ?? null) ?: (
            is_array($firstGalleryImage) && filled($firstGalleryImage['url'] ?? null)
                ? $firstGalleryImage['url']
                : asset('images/product-placeholder.svg')
        ));
        $initialWishlisted = $isAuthenticatedCustomer
            ? ProductWishlist::query()
                ->where('user_id', $customer->getKey())
                ->where('product_id', $productId)
                ->exists()
            : false;

        $productSocial = [
            'product_id' => $productId,
            'slug' => (string) $product['slug'],
            'title' => (string) $product['title'],
            'url' => $canonicalUrl,
            'image' => $productImage,
            'summary' => (string) ($product['summary'] ?? ''),
            'price' => (float) ($product['base_price'] ?? 0),
            'currency' => (string) ($product['currency'] ?? 'USD'),
            'favorites_count' => max(0, (int) ($product['favorites_count'] ?? 0)),
            'authenticated' => $isAuthenticatedCustomer,
            'initial_wishlisted' => $initialWishlisted,
            'wishlist_endpoint' => $isAuthenticatedCustomer
                ? route('wishlist.products.update', ['product' => $productId])
                : null,
            'wishlist_url' => route('wishlist.index'),
            'login_url' => route('login', ['redirect' => $request->fullUrl()]),
            'guest_storage_key' => 'nextplay:guest-wishlist:v1',
        ];

        return view('storefront.products.show', [
            'product' => $product,
            'cartEditItem' => $cartEditItem,
            'productSocial' => $productSocial,
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
