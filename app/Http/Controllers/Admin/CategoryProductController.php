<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryProductAssignmentSyncService;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Storefront\ProductCatalogCacheService;
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
        private readonly ProductCatalogCacheService $productCatalogCache,
    ) {
    }

    public function index(Request $request, Category $category): View
    {
        $category->load([
            'parent:id,name,slug,parent_id',
            'children:id,parent_id,name,slug,depth,tree_path',
            'ancestors' => fn ($builder) => $builder->select('categories.id', 'categories.name', 'categories.slug', 'categories.parent_id'),
        ]);

        $search = trim(mb_substr((string) $request->query('q', ''), 0, 100));
        $listingCategoryIds = $this->listingCategoryIds($category);
        $categoryIsLeaf = $category->isLeaf();

        $baseQuery = Product::query()
            ->select(['id', 'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'status', 'is_active', 'updated_at', 'updated_by'])
            ->where(function ($builder) use ($listingCategoryIds): void {
                $builder->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $listingCategoryIds))
                    ->orWhereIn('category_id', $listingCategoryIds)
                    ->orWhereIn('subcategory_id', $listingCategoryIds);
            });

        $categoryProductCount = (clone $baseQuery)->count();

        $query = $baseQuery
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
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search) . '%';

            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        $subcategoryOptions = $this->subcategoryOptionsForPicker($category);
        $attachProductSearch = trim(mb_substr((string) $request->query('assign_q', ''), 0, 100));
        $assignableProductOptions = $categoryIsLeaf
            ? $this->assignableProductOptions($category, $attachProductSearch)
            : collect();

        $bulkCategoryOptions = Category::query()
            ->leaf()
            ->select(['id', 'parent_id', 'name', 'slug', 'depth', 'tree_path'])
            ->where('id', '!=', $category->id)
            ->orderBy('tree_path')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.products', [
            'category' => $category,
            'categoryIsLeaf' => $categoryIsLeaf,
            'leafDestinationOptions' => $subcategoryOptions,
            'breadcrumbs' => $category->ancestors->values()->push($category),
            'categoryProductCount' => $categoryProductCount,
            'products' => $query->paginate($this->adminPerPage(30))->withQueryString(),
            'filters' => [
                'q' => $search,
                'assign_q' => $attachProductSearch,
            ],
            'subcategoryOptions' => $subcategoryOptions,
            'bulkCategoryOptions' => $bulkCategoryOptions,
            'assignableProductOptions' => $assignableProductOptions,
        ]);
    }

    /**
     * Return products that can be directly attached to the current category.
     *
     * The filtered assignment table only shows products that are already assigned
     * to the selected category. A freshly-created category therefore has an empty
     * table, so this lightweight picker lets admins seed that category with
     * existing products without first visiting another category page.
     */
    private function assignableProductOptions(Category $category, string $search)
    {
        $query = Product::query()
            ->select(['id', 'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'status', 'is_active', 'updated_at'])
            ->whereDoesntHave('categories', fn ($builder) => $builder->whereKey($category->id))
            ->with([
                'category:id,name,parent_id',
                'subcategory:id,name,parent_id',
                'images:id,product_id,path,url,alt_text,is_primary,sort_order',
            ]);

        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->limit(100)
            ->get();
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

        if (! $category->isLeaf()) {
            return $category->descendants()
                ->select($columns)
                ->whereDoesntHave('children')
                ->orderBy('categories.tree_path')
                ->orderBy('categories.sort_order')
                ->orderBy('categories.name')
                ->get();
        }

        $rootId = DB::table('category_closure')
            ->where('descendant_id', $category->id)
            ->orderByDesc('depth')
            ->value('ancestor_id') ?: $category->id;

        return Category::query()
            ->leaf()
            ->select($columns)
            ->whereIn('categories.id', function ($query) use ($rootId): void {
                $query->select('descendant_id')
                    ->from('category_closure')
                    ->where('ancestor_id', $rootId);
            })
            ->where('categories.id', '!=', $category->id)
            ->orderBy('categories.tree_path')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.name')
            ->get();
    }

    /** @return array<int, int> */
    private function listingCategoryIds(Category $category): array
    {
        return collect($this->treeService->descendantIds((int) $category->id, true))
            ->push((int) $category->id)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function syncLegacyAssignments(): RedirectResponse
    {
        $stats = $this->assignmentSyncService->syncAllProductCategoryAssignments(resetExisting: true);

        return back()->with(
            'status',
            sprintf(
                'Leaf-only category assignments rebuilt. %d old rows removed, %d trusted leaf assignments created, %d products checked.',
                $stats['assignments_deleted'],
                $stats['legacy_assignments_created'] + $stats['trusted_rule_assignments_created'],
                $stats['products_scanned']
            )
        );
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'visible_product_ids' => ['nullable', 'array', 'max:100'],
            'visible_product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'attach_product_ids' => ['nullable', 'array', 'max:100'],
            'attach_product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'selected_product_ids' => ['nullable', 'array', 'max:100'],
            'selected_product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'bulk_selected_product_ids' => ['nullable', 'array', 'max:100'],
            'bulk_selected_product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'bulk_category_ids' => ['nullable', 'array', 'max:50'],
            'bulk_category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'assignment_action' => ['nullable', 'string', Rule::in(['attach_products', 'bulk_assign', 'save_category_changes'])],
            'product_category_updates' => ['nullable', 'array', 'max:100'],
            'product_category_updates.*.add_category_ids' => ['nullable', 'array', 'max:50'],
            'product_category_updates.*.add_category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'product_category_updates.*.remove_category_ids' => ['nullable', 'array', 'max:50'],
            'product_category_updates.*.remove_category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
        ]);

        $assignmentAction = (string) ($validated['assignment_action'] ?? 'save_category_changes');

        if ($assignmentAction === 'attach_products' && ! $category->isLeaf()) {
            return back()->withErrors([
                'attach_product_ids' => 'Products can only be assigned to a last-level category. Choose one of this category’s leaf children first.',
            ]);
        }

        $visibleIds = collect($validated['visible_product_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $visibleIdSet = $visibleIds->flip();
        $attachProductIds = collect($validated['attach_product_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
        $rowUpdates = collect($validated['product_category_updates'] ?? []);
        $selectedProductIds = collect($validated['selected_product_ids'] ?? [])
            ->merge($validated['bulk_selected_product_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $visibleIdSet->has($id))
            ->unique()
            ->values();
        $bulkCategoryIds = collect($validated['bulk_category_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $requestedAddCategoryIds = $bulkCategoryIds
            ->merge($rowUpdates->flatMap(function ($input): array {
                return is_array($input) ? (array) ($input['add_category_ids'] ?? []) : [];
            }))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $nonLeafCategoryNames = Category::query()
            ->whereIn('id', $requestedAddCategoryIds->all())
            ->whereHas('children')
            ->pluck('name');

        if ($nonLeafCategoryNames->isNotEmpty()) {
            return back()->withErrors([
                'bulk_category_ids' => 'Products can only be assigned to last-level categories. Remove: '.$nonLeafCategoryNames->join(', ').'.',
            ])->withInput();
        }

        if ($assignmentAction === 'attach_products' && $attachProductIds->isEmpty()) {
            return back()
                ->withErrors(['attach_product_ids' => 'Please select at least one product before clicking Add selected products.'])
                ->withInput();
        }

        if ($assignmentAction === 'bulk_assign' && ($selectedProductIds->isEmpty() || $bulkCategoryIds->isEmpty())) {
            return back()
                ->withErrors(['bulk_category_ids' => 'Select at least one visible product and one category before applying bulk assignment.'])
                ->withInput();
        }

        $changedProducts = collect();

        DB::transaction(function () use ($category, $visibleIds, $attachProductIds, $rowUpdates, $selectedProductIds, $bulkCategoryIds, $changedProducts): void {
            $lockProductIds = $visibleIds->merge($attachProductIds)->unique()->values();

            if ($lockProductIds->isNotEmpty()) {
                DB::table('category_product')->whereIn('product_id', $lockProductIds->all())->lockForUpdate()->get();
            }

            if ($attachProductIds->isNotEmpty()) {
                foreach ($attachProductIds as $productId) {
                    $hasPrimaryCategory = DB::table('category_product')
                        ->where('product_id', $productId)
                        ->where('is_primary', true)
                        ->exists();

                    $didChange = $this->applyCategoryChangesToProduct($productId, [(int) $category->id], []);

                    if (! $hasPrimaryCategory) {
                        $this->markCategoryAsPrimary($productId, (int) $category->id);
                        $didChange = true;
                    }

                    if ($didChange) {
                        $changedProducts->push($productId);
                    }
                }
            }

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

                if ($this->applyCategoryChangesToProduct($productId, $addIds->all(), $removeIds->all())) {
                    $changedProducts->push($productId);
                }
            }

            if ($selectedProductIds->isNotEmpty() && $bulkCategoryIds->isNotEmpty()) {
                foreach ($selectedProductIds as $productId) {
                    if ($this->applyCategoryChangesToProduct($productId, $bulkCategoryIds->all(), [])) {
                        $changedProducts->push($productId);
                    }
                }
            }

            $changedProducts->unique()->each(fn (int $productId): mixed => $this->normalizePrimaryCategory($productId));
        });

        $changedProductIds = $changedProducts->unique()->values();

        if ($changedProductIds->isNotEmpty()) {
            Product::query()
                ->whereIn('id', $changedProductIds->all())
                ->update([
                    'last_update_summary' => 'Category assignments updated from '.$category->name,
                    'updated_by' => auth('admin')->id(),
                    'updated_at' => now(),
                ]);
        }

        $this->treeService->flushCache();
        $this->productCatalogCache->flush();

        $changedCount = $changedProductIds->count();

        if ($changedCount > 0) {
            $message = $assignmentAction === 'attach_products'
                ? "Added {$changedCount} product(s) to {$category->name}. Counts are refreshed."
                : "Category assignments updated for {$changedCount} product(s). Counts are refreshed.";

            return back()->with('status', $message);
        }

        $message = $assignmentAction === 'attach_products'
            ? 'The selected products are already assigned to this category. Refresh the page and try another product if needed.'
            : 'No changes were saved because no product/category changes were selected.';

        return back()->withErrors(['category_assignment' => $message]);
    }

    public function destroy(Category $category, Product $product): RedirectResponse
    {
        $removed = false;

        DB::transaction(function () use ($category, $product, &$removed): void {
            Product::query()->whereKey($product->id)->lockForUpdate()->first();
            DB::table('category_product')
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->get();

            $deletedPivotRows = DB::table('category_product')
                ->where('product_id', $product->id)
                ->where('category_id', $category->id)
                ->delete();

            $legacyDirectAssignment = (int) $product->subcategory_id === (int) $category->id
                || ($product->subcategory_id === null && (int) $product->category_id === (int) $category->id);

            if ($deletedPivotRows === 0 && ! $legacyDirectAssignment) {
                return;
            }

            $removed = true;
            $this->normalizePrimaryCategory((int) $product->id);
        });

        if (! $removed) {
            return back()->withErrors([
                'category_assignment' => "{$product->name} is not directly assigned to {$category->name}.",
            ]);
        }

        Product::query()->whereKey($product->id)->update([
            'last_update_summary' => 'Removed from category '.$category->name,
            'updated_by' => auth('admin')->id(),
            'updated_at' => now(),
        ]);

        $this->treeService->flushCache();
        $this->productCatalogCache->flush();

        return back()->with('status', "{$product->name} was removed from {$category->name}.");
    }

    private function markCategoryAsPrimary(int $productId, int $categoryId): void
    {
        DB::table('category_product')
            ->where('product_id', $productId)
            ->update(['is_primary' => false, 'updated_at' => now()]);

        DB::table('category_product')
            ->where('product_id', $productId)
            ->where('category_id', $categoryId)
            ->update(['is_primary' => true, 'updated_at' => now()]);
    }

    /** @param array<int, int> $addCategoryIds @param array<int, int> $removeCategoryIds */
    private function applyCategoryChangesToProduct(int $productId, array $addCategoryIds, array $removeCategoryIds): bool
    {
        $now = now();
        $changed = false;

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

            $deleted = DB::table('category_product')
                ->where('product_id', $productId)
                ->where('category_id', $categoryId)
                ->where('is_primary', false)
                ->delete();

            if ($deleted > 0) {
                $changed = true;
            }
        }

        $leafAddCategoryIds = collect($addCategoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $existingIds = DB::table('category_product')
            ->where('product_id', $productId)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        foreach ($leafAddCategoryIds as $categoryId) {
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

            $changed = true;
        }

        return $changed;
    }


    private function normalizePrimaryCategory(int $productId): void
    {
        $leafCategoryIds = Category::query()
            ->leaf()
            ->whereIn('id', DB::table('category_product')->where('product_id', $productId)->select('category_id'))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        DB::table('category_product')
            ->where('product_id', $productId)
            ->when(
                $leafCategoryIds === [],
                fn ($query) => $query,
                fn ($query) => $query->whereNotIn('category_id', $leafCategoryIds)
            )
            ->delete();

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
