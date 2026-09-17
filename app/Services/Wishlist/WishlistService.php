<?php

namespace App\Services\Wishlist;

use App\Models\Product;
use App\Models\ProductWishlist;
use App\Models\User;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Support\Facades\DB;

final class WishlistService
{
    private const GUEST_SESSION_KEY = 'nextplay_api.wishlist.product_ids';

    public function __construct(private readonly ProductCatalogService $catalog)
    {
    }

    /** @return array<string, mixed> */
    public function summary(?User $user): array
    {
        $customer = $user?->isCustomer() ? $user : null;
        $productIds = $customer
            ? ProductWishlist::query()
                ->where('user_id', $customer->getKey())
                ->whereHas('product', fn ($query) => $query->published())
                ->latest()
                ->pluck('product_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all()
            : $this->guestIds();

        $products = Product::query()
            ->published()
            ->with($this->catalog->listingRelations())
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy(fn (Product $product): int => (int) $product->getKey());

        $items = collect($productIds)
            ->map(fn (int $productId) => $products->get($productId))
            ->filter(fn ($product): bool => $product instanceof Product)
            ->map(fn (Product $product): array => $this->catalog->fromListingModel($product))
            ->values()
            ->all();

        $validIds = collect($items)->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        if (! $customer && $validIds !== $productIds) {
            session()->put(self::GUEST_SESSION_KEY, $validIds);
        }

        return [
            'items' => $items,
            'product_ids' => $validIds,
            'count' => count($validIds),
            'authenticated' => $customer !== null,
        ];
    }

    /** @return array<string, mixed> */
    public function add(?User $user, int $productId): array
    {
        $product = Product::query()->published()->whereKey($productId)->first();
        abort_if(! $product, 404);

        if ($user?->isCustomer()) {
            $this->setAuthenticated($user, (int) $product->getKey(), true);
        } else {
            $ids = collect($this->guestIds())
                ->push((int) $product->getKey())
                ->unique()
                ->take(100)
                ->values()
                ->all();
            session()->put(self::GUEST_SESSION_KEY, $ids);
        }

        return $this->summary($user);
    }

    /** @return array<string, mixed> */
    public function remove(?User $user, int $productId): array
    {
        if ($user?->isCustomer()) {
            $this->setAuthenticated($user, $productId, false);
        } else {
            session()->put(
                self::GUEST_SESSION_KEY,
                collect($this->guestIds())->reject(fn (int $id): bool => $id === $productId)->values()->all()
            );
        }

        return $this->summary($user);
    }

    /** @return array<string, mixed> */
    public function setWishlisted(User $user, Product $product, bool $wishlisted): array
    {
        abort_unless($user->isCustomer(), 403);
        abort_unless($product->is_active && $product->status === 'active', 404);

        $this->setAuthenticated($user, (int) $product->getKey(), $wishlisted);

        return $this->summary($user);
    }

    private function setAuthenticated(User $user, int $productId, bool $wishlisted): void
    {
        DB::transaction(function () use ($user, $productId, $wishlisted): void {
            if ($wishlisted) {
                $record = ProductWishlist::query()->firstOrCreate([
                    'user_id' => $user->getKey(),
                    'product_id' => $productId,
                ]);

                if ($record->wasRecentlyCreated) {
                    Product::query()->whereKey($productId)->update([
                        'favorites_count' => DB::raw('COALESCE(favorites_count, 0) + 1'),
                    ]);
                }

                return;
            }

            $removed = ProductWishlist::query()
                ->where('user_id', $user->getKey())
                ->where('product_id', $productId)
                ->delete();

            if ($removed > 0) {
                Product::query()->whereKey($productId)->update([
                    'favorites_count' => DB::raw(
                        'CASE WHEN COALESCE(favorites_count, 0) > 0 THEN COALESCE(favorites_count, 0) - 1 ELSE 0 END'
                    ),
                ]);
            }
        });
    }

    /** @return array<int, int> */
    private function guestIds(): array
    {
        return collect((array) session(self::GUEST_SESSION_KEY, []))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }
}
