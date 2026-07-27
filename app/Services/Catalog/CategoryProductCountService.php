<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryProductCountService
{
    /**
     * Return the unique product total for every category subtree.
     *
     * Products are assigned only to leaf categories, but each parent category
     * must display the combined total from every descendant level. The hierarchy
     * is resolved from categories.parent_id rather than depending exclusively on
     * category_closure, so the admin count remains correct while closure data is
     * being rebuilt after an import or hierarchy change.
     *
     * Legacy category_id/subcategory_id assignments are included alongside the
     * category_product pivot for backward compatibility. A product assigned to
     * more than one leaf in the same subtree is counted once for that ancestor.
     * Soft-deleted products are excluded.
     *
     * @return array<int, int>
     */
    public function allCounts(): array
    {
        $categories = Category::query()
            ->get(['id', 'parent_id']);

        if ($categories->isEmpty()) {
            return [];
        }

        $categoryIds = $categories
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        /** @var array<int, array<int, true>> $directProducts */
        $directProducts = [];
        /** @var array<int, array<int, true>> $childrenByParent */
        $childrenByParent = [];

        foreach ($categories as $category) {
            $categoryId = (int) $category->id;
            $directProducts[$categoryId] = [];

            if ($category->parent_id !== null) {
                $parentId = (int) $category->parent_id;
                $childrenByParent[$parentId][$categoryId] = true;
            }
        }

        $addAssignment = static function (int $categoryId, int $productId) use (&$directProducts): void {
            if ($categoryId <= 0 || $productId <= 0 || ! array_key_exists($categoryId, $directProducts)) {
                return;
            }

            $directProducts[$categoryId][$productId] = true;
        };

        foreach (DB::table('category_product as cp')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->whereIn('cp.category_id', $categoryIds)
            ->whereNull('p.deleted_at')
            ->select(['cp.category_id', 'cp.product_id'])
            ->orderBy('cp.category_id')
            ->orderBy('cp.product_id')
            ->cursor() as $row) {
            $addAssignment((int) $row->category_id, (int) $row->product_id);
        }

        foreach (DB::table('products')
            ->whereNull('deleted_at')
            ->whereNotNull('category_id')
            ->whereIn('category_id', $categoryIds)
            ->select(['id', 'category_id'])
            ->orderBy('id')
            ->cursor() as $row) {
            $addAssignment((int) $row->category_id, (int) $row->id);
        }

        foreach (DB::table('products')
            ->whereNull('deleted_at')
            ->whereNotNull('subcategory_id')
            ->whereIn('subcategory_id', $categoryIds)
            ->select(['id', 'subcategory_id'])
            ->orderBy('id')
            ->cursor() as $row) {
            $addAssignment((int) $row->subcategory_id, (int) $row->id);
        }

        /** @var array<int, array<int, true>> $productsBySubtree */
        $productsBySubtree = [];
        /** @var array<int, true> $visiting */
        $visiting = [];

        $resolve = function (int $categoryId) use (&$resolve, &$productsBySubtree, &$visiting, $directProducts, $childrenByParent): array {
            if (isset($productsBySubtree[$categoryId])) {
                return $productsBySubtree[$categoryId];
            }

            // Invalid imported cycles should never block the category page. Count
            // the products directly attached to the current node and stop walking.
            if (isset($visiting[$categoryId])) {
                return $directProducts[$categoryId] ?? [];
            }

            $visiting[$categoryId] = true;
            $products = $directProducts[$categoryId] ?? [];

            foreach (array_keys($childrenByParent[$categoryId] ?? []) as $childId) {
                foreach ($resolve((int) $childId) as $productId => $_assigned) {
                    $products[(int) $productId] = true;
                }
            }

            unset($visiting[$categoryId]);

            return $productsBySubtree[$categoryId] = $products;
        };

        $counts = [];
        foreach ($categoryIds as $categoryId) {
            $counts[$categoryId] = count($resolve($categoryId));
        }

        return $counts;
    }
}
