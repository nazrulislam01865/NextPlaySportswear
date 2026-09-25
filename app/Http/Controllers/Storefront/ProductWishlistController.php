<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWishlist;
use App\Services\Storefront\ProductCatalogService;
use App\Services\Wishlist\WishlistHeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductWishlistController extends Controller
{
    /** @var array<int, string> */
    private const SORTS = [
        'recent',
        'oldest',
        'price_asc',
        'price_desc',
        'name_asc',
    ];

    public function index(Request $request, ProductCatalogService $catalog): View
    {
        $user = $request->user('web');
        $isAuthenticatedCustomer = $user?->isCustomer() ?? false;
        $sort = $this->normalizeSort($request->query('sort'));
        $items = collect();

        if ($isAuthenticatedCustomer) {
            $items = ProductWishlist::query()
                ->where('user_id', $user->getKey())
                ->whereHas('product', fn ($query) => $query->published())
                ->with([
                    'product' => fn ($query) => $query->with($catalog->listingRelations()),
                ])
                ->get()
                ->map(fn (ProductWishlist $wishlist): array => $this->productCard(
                    $catalog,
                    $wishlist->product,
                    $wishlist->created_at?->toIso8601String(),
                    route('wishlist.products.update', ['product' => $wishlist->product_id]),
                ));

            $items = $this->sortItems($items, $sort);
        }

        return view('storefront.wishlist.index', [
            'items' => $items,
            'isAuthenticatedCustomer' => $isAuthenticatedCustomer,
            'guestStorageKey' => 'nextplay:guest-wishlist:v1',
            'guestProductsEndpoint' => route('wishlist.guest-products'),
            'loginUrl' => route('login', ['redirect' => route('wishlist.index')]),
            'sort' => $sort,
            'seo' => [
                'title' => 'My Wishlist | '.config('storefront.name'),
                'description' => 'Review the NextPlay Sportswear products you saved for later.',
                'robots' => 'noindex, follow',
                'canonical' => route('wishlist.index'),
            ],
        ]);
    }

    public function guestProducts(Request $request, ProductCatalogService $catalog): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'max:100'],
            'product_ids.*' => ['integer', 'distinct', 'min:1'],
        ]);

        $products = Product::query()
            ->published()
            ->with($catalog->listingRelations())
            ->whereIn('id', $validated['product_ids'])
            ->get()
            ->map(fn (Product $product): array => $this->productCard($catalog, $product))
            ->keyBy(fn (array $product): string => (string) $product['id']);

        return response()->json([
            'products' => $products,
        ]);
    }

    public function preview(Request $request, WishlistHeaderService $wishlistHeader): JsonResponse
    {
        $user = $request->user('web');
        abort_unless($user && $user->isCustomer(), 403);

        return response()->json($wishlistHeader->summary($user, 4));
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'max:100'],
            'product_ids.*' => ['integer', 'distinct', 'min:1'],
        ]);

        $user = $request->user('web');
        abort_unless($user && $user->isCustomer(), 403);

        $productIds = ProductWishlist::query()
            ->where('user_id', $user->getKey())
            ->whereIn('product_id', $validated['product_ids'])
            ->whereHas('product', fn ($query) => $query->published())
            ->pluck('product_id')
            ->map(fn ($productId): int => (int) $productId)
            ->values();

        return response()->json([
            'product_ids' => $productIds,
            'wishlist_count' => ProductWishlist::query()
                ->where('user_id', $user->getKey())
                ->whereHas('product', fn ($query) => $query->published())
                ->count(),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'wishlisted' => ['required', 'boolean'],
        ]);

        $user = $request->user('web');

        abort_unless($user && $user->isCustomer(), 403);
        abort_unless($product->is_active && $product->status === 'active', 404);

        $wishlisted = (bool) $validated['wishlisted'];

        DB::transaction(function () use ($user, $product, $wishlisted): void {
            if ($wishlisted) {
                $wishlist = ProductWishlist::query()->firstOrCreate([
                    'user_id' => $user->getKey(),
                    'product_id' => $product->getKey(),
                ]);

                if ($wishlist->wasRecentlyCreated) {
                    Product::query()->whereKey($product->getKey())->update([
                        'favorites_count' => DB::raw('COALESCE(favorites_count, 0) + 1'),
                    ]);
                }

                return;
            }

            $removed = ProductWishlist::query()
                ->where('user_id', $user->getKey())
                ->where('product_id', $product->getKey())
                ->delete();

            if ($removed > 0) {
                Product::query()
                    ->whereKey($product->getKey())
                    ->update([
                        'favorites_count' => DB::raw(
                            'CASE WHEN COALESCE(favorites_count, 0) > 0 THEN COALESCE(favorites_count, 0) - 1 ELSE 0 END'
                        ),
                    ]);
            }
        });

        $product->refresh();

        return response()->json([
            'wishlisted' => $wishlisted,
            'wishlist_count' => ProductWishlist::query()
                ->where('user_id', $user->getKey())
                ->whereHas('product', fn ($query) => $query->published())
                ->count(),
            'favorites_count' => max(0, (int) ($product->favorites_count ?? 0)),
            'message' => $wishlisted
                ? 'Added to your wishlist'
                : 'Removed from your wishlist',
        ]);
    }

    private function normalizeSort(mixed $sort): string
    {
        $sort = trim((string) $sort);

        return in_array($sort, self::SORTS, true) ? $sort : 'recent';
    }

    /**
     * Keep authenticated sorting on the server so the selector and page URL stay
     * deterministic. Guest sorting uses the same keys in storefront.js after the
     * saved products have been resolved from browser storage.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function sortItems(Collection $items, string $sort): Collection
    {
        return $items->sort(function (array $left, array $right) use ($sort): int {
            if ($sort === 'name_asc') {
                $nameComparison = strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));

                return $nameComparison !== 0
                    ? $nameComparison
                    : $this->compareSavedAt($left, $right, true);
            }

            if (in_array($sort, ['price_asc', 'price_desc'], true)) {
                $leftAvailable = (bool) ($left['price_available'] ?? false);
                $rightAvailable = (bool) ($right['price_available'] ?? false);

                if ($leftAvailable !== $rightAvailable) {
                    return $leftAvailable ? -1 : 1;
                }

                if ($leftAvailable && $rightAvailable) {
                    $priceComparison = (float) ($left['price'] ?? 0) <=> (float) ($right['price'] ?? 0);
                    if ($priceComparison !== 0) {
                        return $sort === 'price_desc' ? -$priceComparison : $priceComparison;
                    }
                }

                return $this->compareSavedAt($left, $right, true);
            }

            return $this->compareSavedAt($left, $right, $sort !== 'oldest');
        })->values();
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private function compareSavedAt(array $left, array $right, bool $recentFirst): int
    {
        $comparison = strcmp((string) ($left['saved_at'] ?? ''), (string) ($right['saved_at'] ?? ''));

        return $recentFirst ? -$comparison : $comparison;
    }

    /** @return array<string, mixed> */
    private function productCard(
        ProductCatalogService $catalog,
        Product $product,
        ?string $savedAt = null,
        ?string $removeEndpoint = null,
    ): array {
        $card = $catalog->fromListingModel($product);
        $displayPrice = max(0, (float) ($card['display_unit_price'] ?? 0));
        $category = trim((string) ($product->subcategory?->name ?: ($card['category'] ?? '')));
        $currency = trim((string) ($card['currency'] ?? $product->currency ?? 'USD'));
        $currency = $currency !== '' ? $currency : 'USD';

        return [
            'id' => (int) $product->getKey(),
            'slug' => (string) $product->slug,
            'title' => (string) ($card['title'] ?? $product->name),
            'url' => (string) ($card['url'] ?? route('products.show', ['slug' => $product->slug])),
            'image' => (string) ($card['image'] ?? asset('images/product-placeholder.svg')),
            'alt' => (string) ($card['alt'] ?? $product->name),
            'category' => $category,
            'price' => $displayPrice,
            'price_available' => $displayPrice > 0,
            'currency' => $currency,
            'saved_at' => $savedAt,
            'remove_endpoint' => $removeEndpoint,
        ];
    }
}
