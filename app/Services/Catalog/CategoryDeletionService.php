<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Services\Storefront\ProductCatalogCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryDeletionService
{
    public function __construct(
        private readonly CategoryMediaService $mediaService,
        private readonly CategoryTreeService $treeService,
        private readonly NavigationService $navigationService,
        private readonly ProductCatalogCacheService $productCatalogCache,
    ) {
    }

    /**
     * @param Collection<int, Category> $categories
     * @return array<int, array{
     *     category_count:int,
     *     child_category_count:int,
     *     category_names:array<int,string>,
     *     product_count:int,
     *     product_names:array<int,string>,
     *     menu_item_count:int
     * }>
     */
    public function impactsFor(Collection $categories): array
    {
        $ancestorIds = $categories
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ancestorIds->isEmpty()) {
            return [];
        }

        $descendantRows = DB::table('category_closure as cc')
            ->join('categories as affected_categories', 'affected_categories.id', '=', 'cc.descendant_id')
            ->whereIn('cc.ancestor_id', $ancestorIds->all())
            ->whereNull('affected_categories.deleted_at')
            ->select([
                'cc.ancestor_id',
                'cc.descendant_id',
                'cc.depth',
                'affected_categories.name',
                'affected_categories.tree_path',
            ])
            ->orderBy('cc.ancestor_id')
            ->orderBy('affected_categories.tree_path')
            ->orderBy('affected_categories.name')
            ->get()
            ->groupBy(fn ($row): int => (int) $row->ancestor_id);

        $productRows = $this->affectedProductsQuery($ancestorIds->all())
            ->get()
            ->groupBy(fn ($row): int => (int) $row->ancestor_id);

        $menuCounts = DB::table('category_closure as cc')
            ->join('menu_items', 'menu_items.category_id', '=', 'cc.descendant_id')
            ->whereIn('cc.ancestor_id', $ancestorIds->all())
            ->selectRaw('cc.ancestor_id, COUNT(DISTINCT menu_items.id) AS aggregate')
            ->groupBy('cc.ancestor_id')
            ->pluck('aggregate', 'cc.ancestor_id');

        return $categories->mapWithKeys(function (Category $category) use ($descendantRows, $productRows, $menuCounts): array {
            $rows = collect($descendantRows->get((int) $category->id, collect()))
                ->unique(fn ($row): int => (int) $row->descendant_id)
                ->values();
            $products = collect($productRows->get((int) $category->id, collect()))
                ->unique(fn ($row): int => (int) $row->product_id)
                ->values();

            return [(int) $category->id => [
                'category_count' => $rows->count(),
                'child_category_count' => max(0, $rows->count() - 1),
                'category_names' => $rows
                    ->reject(fn ($row): bool => (int) $row->descendant_id === (int) $category->id)
                    ->pluck('name')
                    ->map(fn ($name): string => (string) $name)
                    ->take(12)
                    ->values()
                    ->all(),
                'product_count' => $products->count(),
                'product_names' => $products
                    ->pluck('product_name')
                    ->map(fn ($name): string => (string) $name)
                    ->take(12)
                    ->values()
                    ->all(),
                'menu_item_count' => (int) ($menuCounts[(int) $category->id] ?? 0),
            ]];
        })->all();
    }

    /**
     * @return array{
     *     category_count:int,
     *     child_category_count:int,
     *     category_names:array<int,string>,
     *     product_count:int,
     *     product_names:array<int,string>,
     *     menu_item_count:int
     * }
     */
    public function deleteSubtree(Category $category): array
    {
        $impact = $this->impactsFor(collect([$category]))[(int) $category->id] ?? [
            'category_count' => 1,
            'child_category_count' => 0,
            'category_names' => [],
            'product_count' => 0,
            'product_names' => [],
            'menu_item_count' => 0,
        ];

        DB::transaction(function () use ($category): void {
            $subtreeIds = $this->lockedSubtreeIds((int) $category->id);

            $subtree = Category::query()
                ->whereIn('id', $subtreeIds->all())
                ->orderByDesc('depth')
                ->lockForUpdate()
                ->get();

            if ($subtree->isEmpty()) {
                return;
            }

            $affectedProductIds = $this->affectedProductIdsForSubtree($subtreeIds->all());
            $adminId = auth('admin')->id() ?: auth()->id();

            if ($affectedProductIds->isNotEmpty()) {
                // Affected products remain in the catalog, but deleting their category
                // intentionally makes them fully categoryless as requested.
                DB::table('category_product')
                    ->whereIn('product_id', $affectedProductIds->all())
                    ->delete();

                Product::withTrashed()
                    ->whereIn('id', $affectedProductIds->all())
                    ->update([
                        'category_id' => null,
                        'subcategory_id' => null,
                        'last_update_summary' => 'Category assignment removed because its category tree was deleted.',
                        'updated_by' => $adminId,
                        'updated_at' => now(),
                    ]);
            }

            DB::table('menu_items')
                ->whereIn('category_id', $subtreeIds->all())
                ->update([
                    'category_id' => null,
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            foreach ($subtree as $node) {
                $this->mediaService->deleteAll($node);
                $node->forceFill(['updated_by' => $adminId])->saveQuietly();
                $node->delete();
            }

            $this->treeService->rebuildClosure();
        });

        $this->navigationService->flushCache();
        $this->productCatalogCache->flush();

        return $impact;
    }


    /** @return Collection<int, int> */
    private function lockedSubtreeIds(int $rootCategoryId): Collection
    {
        $subtreeIds = collect([$rootCategoryId]);
        $frontier = collect([$rootCategoryId]);

        while ($frontier->isNotEmpty()) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier->all())
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => ! $subtreeIds->contains($id))
                ->unique()
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $subtreeIds = $subtreeIds->merge($children)->unique()->values();
            $frontier = $children;
        }

        return $subtreeIds;
    }

    /** @param array<int, int> $ancestorIds */
    private function affectedProductsQuery(array $ancestorIds)
    {
        $pivot = DB::table('category_closure as cc')
            ->join('category_product as cp', 'cp.category_id', '=', 'cc.descendant_id')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->whereIn('cc.ancestor_id', $ancestorIds)
            ->selectRaw('cc.ancestor_id, p.id AS product_id, p.name AS product_name');

        $legacyCategory = DB::table('category_closure as cc')
            ->join('products as p', 'p.category_id', '=', 'cc.descendant_id')
            ->whereIn('cc.ancestor_id', $ancestorIds)
            ->selectRaw('cc.ancestor_id, p.id AS product_id, p.name AS product_name');

        $legacySubcategory = DB::table('category_closure as cc')
            ->join('products as p', 'p.subcategory_id', '=', 'cc.descendant_id')
            ->whereIn('cc.ancestor_id', $ancestorIds)
            ->selectRaw('cc.ancestor_id, p.id AS product_id, p.name AS product_name');

        return $pivot->union($legacyCategory)->union($legacySubcategory);
    }

    /** @param array<int, int> $subtreeIds @return Collection<int, int> */
    private function affectedProductIdsForSubtree(array $subtreeIds): Collection
    {
        $pivotIds = DB::table('category_product')
            ->whereIn('category_id', $subtreeIds)
            ->pluck('product_id');

        $legacyIds = Product::withTrashed()
            ->where(function ($query) use ($subtreeIds): void {
                $query->whereIn('category_id', $subtreeIds)
                    ->orWhereIn('subcategory_id', $subtreeIds);
            })
            ->pluck('id');

        return $pivotIds
            ->merge($legacyIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }
}
