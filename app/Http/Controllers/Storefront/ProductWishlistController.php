<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductWishlistController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('web');
        $isAuthenticatedCustomer = $user?->isCustomer() ?? false;
        $items = collect();

        if ($isAuthenticatedCustomer) {
            $items = ProductWishlist::query()
                ->where('user_id', $user->getKey())
                ->whereHas('product', fn ($query) => $query->published())
                ->with([
                    'product.images',
                    'product.category',
                    'product.subcategory',
                ])
                ->latest()
                ->get()
                ->map(fn (ProductWishlist $wishlist): array => $this->productCard(
                    $wishlist->product,
                    $wishlist->created_at?->toIso8601String(),
                    route('wishlist.products.update', ['product' => $wishlist->product_id]),
                ));
        }

        return view('storefront.wishlist.index', [
            'items' => $items,
            'isAuthenticatedCustomer' => $isAuthenticatedCustomer,
            'guestStorageKey' => 'nextplay:guest-wishlist:v1',
            'guestProductsEndpoint' => route('wishlist.guest-products'),
            'loginUrl' => route('login', ['redirect' => route('wishlist.index')]),
            'seo' => [
                'title' => 'My Wishlist | '.config('storefront.name'),
                'description' => 'Review the NextPlay Sportswear products you saved for later.',
                'robots' => 'noindex, follow',
                'canonical' => route('wishlist.index'),
            ],
        ]);
    }

    public function guestProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'max:100'],
            'product_ids.*' => ['integer', 'distinct', 'min:1'],
        ]);

        $products = Product::query()
            ->published()
            ->with(['images', 'category', 'subcategory'])
            ->whereIn('id', $validated['product_ids'])
            ->get()
            ->map(fn (Product $product): array => $this->productCard($product))
            ->keyBy(fn (array $product): string => (string) $product['id']);

        return response()->json([
            'products' => $products,
        ]);
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

    private function productCard(Product $product, ?string $savedAt = null, ?string $removeEndpoint = null): array
    {
        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        $category = $product->subcategory?->name ?: $product->category?->name;

        return [
            'id' => (int) $product->getKey(),
            'slug' => (string) $product->slug,
            'title' => (string) $product->name,
            'url' => route('products.show', ['slug' => $product->slug]),
            'image' => $image?->publicUrl() ?: asset('images/product-placeholder.svg'),
            'alt' => $image?->alt_text ?: $product->name,
            'category' => $category,
            'summary' => (string) ($product->short_description ?? ''),
            'price' => (float) $product->base_price,
            'currency' => (string) ($product->currency ?: 'USD'),
            'minimum_quantity' => max(1, (int) ($product->minimum_quantity ?: 1)),
            'saved_at' => $savedAt,
            'remove_endpoint' => $removeEndpoint,
        ];
    }
}
