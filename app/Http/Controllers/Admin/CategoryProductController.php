<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryProductAssignmentSyncService;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryProductController extends Controller
{
    public function __construct(
        private readonly CategoryTreeService $treeService,
        private readonly CategoryProductAssignmentSyncService $assignmentSyncService,
    ) {
    }

    public function index(Request $request, Category $category): View
    {
        $category->load([
            'parent:id,name,slug,parent_id',
            'ancestors' => fn ($builder) => $builder->select('categories.id', 'categories.name', 'categories.slug', 'categories.parent_id'),
        ]);

        $search = trim(mb_substr((string) $request->query('q', ''), 0, 100));
        $categoryProductCount = $category->products()->count();

        $query = Product::query()
            ->select(['id', 'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'status', 'is_active', 'updated_at', 'updated_by'])
            ->whereHas('categories', fn ($builder) => $builder->whereKey($category->id))
            ->with([
                'categories' => function ($builder): void {
                    $builder->select('categories.id', 'categories.parent_id', 'categories.name', 'categories.slug', 'categories.depth', 'categories.tree_path')
                        ->with('parent:id,name,slug,parent_id')
                        ->orderByDesc('category_product.is_primary')
                        ->orderBy('categories.tree_path')
                        ->orderBy('category_product.sort_order')
                        ->orderBy('categories.name');
                },
                'images:id,product_id,path,url,alt_text,is_primary,sort_order',
                'updater:id,name',
            ])
            ->orderBy('name')
            ->orderBy('id');

        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        $subcategoryOptions = $this->subcategoryOptionsForPicker($category);

        $bulkCategoryOptions = Category::query()
            ->select(['id', 'parent_id', 'name', 'slug', 'depth', 'tree_path'])
            ->where('id', '!=', $category->id)
            ->orderBy('tree_path')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.products', [
            'category' => $category,
            'breadcrumbs' => $category->ancestors->values()->push($category),
            'categoryProductCount' => $categoryProductCount,
            'products' => $query->paginate(30)->withQueryString(),
            'filters' => [
                'q' => $search,
            ],
            'subcategoryOptions' => $subcategoryOptions,
            'bulkCategoryOptions' => $bulkCategoryOptions,
        ]);
    }

    /**
     * Return subcategory options for the inline picker.
     *
     * When the filtered category is a parent category, the picker shows its descendants.
     * When the filtered category is already a leaf category, the picker falls back to
     * sibling categories under the same parent. This prevents the UI from showing
     * "No more subcategories available" for leaf-category product lists.
     */
    private function subcategoryOptionsForPicker(Category $category)
    {
        $columns = ['categories.id', 'categories.parent_id', 'categories.name', 'categories.slug', 'categories.depth', 'categories.tree_path'];

        $descendants = $category->descendants()
            ->select($columns)
            ->orderBy('categories.tree_path')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.name')
            ->get();

        if ($descendants->isNotEmpty() || ! $category->parent_id) {
            return $descendants;
        }

        return Category::query()
            ->select($columns)
            ->whereIn('categories.id', function ($query) use ($category): void {
                $query->select('descendant_id')
                    ->from('category_closure')
                    ->where('ancestor_id', $category->parent_id)
                    ->where('descendant_id', '!=', $category->parent_id);
            })
            ->where('categories.id', '!=', $category->id)
            ->orderBy('categories.tree_path')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.name')
            ->get();
    }

    public function syncLegacyAssignments(): RedirectResponse
    {
        $stats = $this->assignmentSyncService->syncAllProductCategoryAssignments(resetExisting: true);

        return back()->with(
            'status',
            sprintf(
                'Category assignments rebuilt. %d old rows removed, %d trusted assignments created, %d parent assignments created, %d products checked.',
                $stats['assignments_deleted'],
                $stats['legacy_assignments_created'] + $stats['trusted_rule_assignments_created'],
                $stats['ancestor_assignments_created'] + $stats['trusted_rule_ancestor_assignments_created'],
                $stats['products_scanned']
            )
        );
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'visible_product_ids' => ['required', 'array', 'max:100'],
            'visible_product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'selected_product_ids' => ['nullable', 'array', 'max:100'],
            'selected_product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'bulk_category_ids' => ['nullable', 'array', 'max:50'],
            'bulk_category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'product_category_updates' => ['nullable', 'array', 'max:100'],
            'product_category_updates.*.add_category_ids' => ['nullable', 'array', 'max:50'],
            'product_category_updates.*.add_category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'product_category_updates.*.remove_category_ids' => ['nullable', 'array', 'max:50'],
            'product_category_updates.*.remove_category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
        ]);

        $visibleIds = collect($validated['visible_product_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $visibleIdSet = $visibleIds->flip();
        $rowUpdates = collect($validated['product_category_updates'] ?? []);
        $selectedProductIds = collect($validated['selected_product_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $visibleIdSet->has($id))
            ->unique()
            ->values();
        $bulkCategoryIds = collect($validated['bulk_category_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $changedProducts = collect();

        DB::transaction(function () use ($visibleIds, $rowUpdates, $selectedProductIds, $bulkCategoryIds, $changedProducts): void {
            DB::table('category_product')->whereIn('product_id', $visibleIds)->lockForUpdate()->get();

            foreach ($visibleIds as $productId) {
                $input = $rowUpdates->get((string) $productId, $rowUpdates->get($productId, []));

                $addIds = collect(is_array($input) ? ($input['add_category_ids'] ?? []) : [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values();

                $removeIds = collect(is_array($input) ? ($input['remove_category_ids'] ?? []) : [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values();

                if ($addIds->isEmpty() && $removeIds->isEmpty()) {
                    continue;
                }

                $this->applyCategoryChangesToProduct($productId, $addIds->all(), $removeIds->all());
                $changedProducts->push($productId);
            }

            if ($selectedProductIds->isNotEmpty() && $bulkCategoryIds->isNotEmpty()) {
                foreach ($selectedProductIds as $productId) {
                    $this->applyCategoryChangesToProduct($productId, $bulkCategoryIds->all(), []);
                    $changedProducts->push($productId);
                }
            }

            $changedProducts->unique()->each(fn (int $productId): mixed => $this->normalizePrimaryCategory($productId));
        });

        $this->treeService->flushCache();

        $changedCount = $changedProducts->unique()->count();

        return back()->with('status', $changedCount > 0
            ? "Category assignments updated for {$changedCount} product(s). Counts are refreshed."
            : 'No category assignment changes were submitted.');
    }

    /** @param array<int, int> $addCategoryIds @param array<int, int> $removeCategoryIds */
    private function applyCategoryChangesToProduct(int $productId, array $addCategoryIds, array $removeCategoryIds): void
    {
        $now = now();

        $primaryCategoryId = DB::table('category_product')
            ->where('product_id', $productId)
            ->where('is_primary', true)
            ->value('category_id');

        $protectedIds = collect([$primaryCategoryId])->filter()->map(fn ($id) => (int) $id)->flip();

        foreach (collect($removeCategoryIds)->unique() as $categoryId) {
            $categoryId = (int) $categoryId;

            if ($protectedIds->has($categoryId)) {
                continue;
            }

            DB::table('category_product')
                ->where('product_id', $productId)
                ->where('category_id', $categoryId)
                ->where('is_primary', false)
                ->delete();
        }

        $expandedAddCategoryIds = $this->expandCategoryIdsWithAncestors($addCategoryIds);

        $existingIds = DB::table('category_product')
            ->where('product_id', $productId)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        foreach ($expandedAddCategoryIds as $categoryId) {
            $categoryId = (int) $categoryId;

            if ($existingIds->has($categoryId)) {
                continue;
            }

            DB::table('category_product')->insert([
                'category_id' => $categoryId,
                'product_id' => $productId,
                'is_primary' => false,
                'is_featured' => false,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }


    /** @param array<int, int> $categoryIds @return array<int, int> */
    private function expandCategoryIdsWithAncestors(array $categoryIds): array
    {
        $categoryIds = collect($categoryIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return [];
        }

        $ancestorIds = DB::table('category_closure')
            ->whereIn('descendant_id', $categoryIds->all())
            ->pluck('ancestor_id')
            ->map(fn ($id) => (int) $id);

        return $categoryIds
            ->merge($ancestorIds)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizePrimaryCategory(int $productId): void
    {
        $assignments = DB::table('category_product')
            ->where('product_id', $productId)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('category_id')
            ->get();

        if ($assignments->isEmpty()) {
            Product::query()->whereKey($productId)->update(['category_id' => null, 'subcategory_id' => null]);
            return;
        }

        $primary = $assignments->firstWhere('is_primary', 1) ?: $assignments->first();
        $primaryCount = $assignments->where('is_primary', 1)->count();

        if ($primaryCount !== 1 || ! (bool) $primary->is_primary) {
            DB::table('category_product')
                ->where('product_id', $productId)
                ->update(['is_primary' => false, 'updated_at' => now()]);

            DB::table('category_product')
                ->where('product_id', $productId)
                ->where('category_id', $primary->category_id)
                ->update(['is_primary' => true, 'updated_at' => now()]);
        }

        $primaryCategory = Category::query()->find($primary->category_id);

        if ($primaryCategory) {
            $this->syncLegacyPrimaryCategory($productId, $primaryCategory);
        }
    }

    private function syncLegacyPrimaryCategory(int $productId, Category $category): void
    {
        $rootId = DB::table('category_closure')
            ->where('descendant_id', $category->id)
            ->orderByDesc('depth')
            ->value('ancestor_id') ?: $category->id;

        Product::query()->whereKey($productId)->update([
            'category_id' => $rootId,
            'subcategory_id' => (int) $rootId === (int) $category->id ? null : $category->id,
        ]);
    }
}
