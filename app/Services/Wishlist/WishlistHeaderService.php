<?php

namespace App\Services\Wishlist;

use App\Models\Product;
use App\Models\ProductWishlist;
use App\Models\User;
use App\Services\Storefront\ProductCatalogService;

class WishlistHeaderService
{
    public function __construct(private readonly ProductCatalogService $catalog)
    {
    }

    /**
     * Build the compact, read-only wishlist data used by the global header.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     total_items: int,
     *     remaining_items: int,
     *     is_empty: bool
     * }
     */
    public function summary(?User $customer, int $limit = 4): array
    {
        $limit = max(1, min($limit, 8));

        if (! $customer?->isCustomer()) {
            return $this->emptySummary();
        }

        $baseQuery = ProductWishlist::query()
            ->where('user_id', $customer->getKey())
            ->whereHas('product', fn ($query) => $query->published());

        $totalItems = (clone $baseQuery)->count();

        $items = $baseQuery
            ->with([
                'product' => fn ($query) => $query
                    ->published()->with(['category', 'subcategory', 'categories', 'images', 'priceTiers']),
            ])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (ProductWishlist $wishlist): ?array {
                $product = $wishlist->product;

                return $product instanceof Product ? $this->productItem($product) : null;
            })
            ->filter()
            ->values()
            ->all();

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'remaining_items' => max(0, $totalItems - count($items)),
            'is_empty' => $totalItems === 0,
        ];
    }

    /** @return array<string, mixed> */
    private function productItem(Product $product): array
    {
        $card = $this->catalog->applySaleCampaignPricing($this->catalog->fromListingModel($product));

        return [
            'id' => (int) $product->getKey(),
            'title' => (string) $product->name,
            'url' => route('products.show', ['slug' => $product->slug]),
            'image' => (string) ($card['image'] ?? asset('images/product-placeholder.svg')),
            'alt' => (string) ($card['alt'] ?? $product->name),
            'price' => (float) ($card['display_unit_price'] ?? 0),
            'original_price' => (float) ($card['original_price'] ?? 0),
            'currency' => (string) ($product->currency ?: 'USD'),
        ];
    }

    /** @return array{items: array<int, array<string, mixed>>, total_items: int, remaining_items: int, is_empty: bool} */
    private function emptySummary(): array
    {
        return [
            'items' => [],
            'total_items' => 0,
            'remaining_items' => 0,
            'is_empty' => true,
        ];
    }
}
