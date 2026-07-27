<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Services\Storefront\ProductCatalogCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeafCategoryAssignmentService
{
    public function __construct(
        private readonly ProductCatalogCacheService $productCatalogCache,
    ) {
    }

    /**
     * Remove direct product assignments from categories that now have children.
     * Products keep any other valid leaf assignments; products with no remaining
     * leaf assignment become categoryless.
     */
    public function enforce(): int
    {
        $affectedProductIds = DB::transaction(function (): Collection {
            $nonLeafCategoryIds = Category::query()
                ->whereHas('children')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            if ($nonLeafCategoryIds->isEmpty()) {
                return collect();
            }

            $pivotProductIds = DB::table('category_product')
                ->whereIn('category_id', $nonLeafCategoryIds->all())
                ->lockForUpdate()
                ->pluck('product_id');

            $legacyProductIds = Product::withTrashed()
                ->where(function ($query) use ($nonLeafCategoryIds): void {
                    $query->whereIn('category_id', $nonLeafCategoryIds->all())
                        ->orWhereIn('subcategory_id', $nonLeafCategoryIds->all());
                })
                ->lockForUpdate()
                ->pluck('id');

            $productIds = $pivotProductIds
                ->merge($legacyProductIds)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            if ($productIds->isEmpty()) {
                return collect();
            }

            DB::table('category_product')
                ->whereIn('category_id', $nonLeafCategoryIds->all())
                ->whereIn('product_id', $productIds->all())
                ->delete();

            foreach ($productIds as $productId) {
                $this->normalizeProduct((int) $productId);
            }

            return $productIds;
        });

        if ($affectedProductIds->isNotEmpty()) {
            $this->productCatalogCache->flush();
        }

        return $affectedProductIds->count();
    }

    private function normalizeProduct(int $productId): void
    {
        $assignments = DB::table('category_product as cp')
            ->join('categories as category', 'category.id', '=', 'cp.category_id')
            ->where('cp.product_id', $productId)
            ->whereNull('category.deleted_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('categories as child')
                    ->whereColumn('child.parent_id', 'category.id')
                    ->whereNull('child.deleted_at');
            })
            ->select(['cp.category_id', 'cp.is_primary', 'cp.sort_order', 'category.depth'])
            ->orderByDesc('cp.is_primary')
            ->orderByDesc('category.depth')
            ->orderBy('cp.sort_order')
            ->orderBy('cp.category_id')
            ->get();

        $adminId = auth('admin')->id();

        if ($assignments->isEmpty()) {
            Product::withTrashed()->whereKey($productId)->update([
                'category_id' => null,
                'subcategory_id' => null,
                'last_update_summary' => 'Category assignment removed because the category now has child categories.',
                'updated_by' => $adminId,
                'updated_at' => now(),
            ]);

            return;
        }

        $primary = $assignments->firstWhere('is_primary', 1) ?: $assignments->first();
        $primaryId = (int) $primary->category_id;

        DB::table('category_product')
            ->where('product_id', $productId)
            ->update(['is_primary' => false, 'updated_at' => now()]);
        DB::table('category_product')
            ->where('product_id', $productId)
            ->where('category_id', $primaryId)
            ->update(['is_primary' => true, 'updated_at' => now()]);

        $rootId = (int) (DB::table('category_closure')
            ->where('descendant_id', $primaryId)
            ->orderByDesc('depth')
            ->value('ancestor_id') ?: $primaryId);

        Product::withTrashed()->whereKey($productId)->update([
            'category_id' => $rootId,
            'subcategory_id' => $rootId === $primaryId ? null : $primaryId,
            'last_update_summary' => 'Category assignments normalized after a category hierarchy change.',
            'updated_by' => $adminId,
            'updated_at' => now(),
        ]);
    }
}
