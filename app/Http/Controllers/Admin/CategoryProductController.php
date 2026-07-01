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
        $search = trim(mb_substr((string) $request->query('q', ''), 0, 100));
        $assignment = in_array($request->query('assignment'), ['assigned', 'unassigned'], true)
            ? (string) $request->query('assignment')
            : '';

        $query = Product::query()
            ->select(['id', 'name', 'slug', 'sku'])
            ->with([
                'categories' => fn ($builder) => $builder->select('categories.id', 'categories.name'),
                'images:id,product_id,path,url,alt_text,is_primary,sort_order',
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

        if ($assignment === 'assigned') {
            $query->whereHas('categories', fn ($builder) => $builder->whereKey($category->id));
        } elseif ($assignment === 'unassigned') {
            $query->whereDoesntHave('categories', fn ($builder) => $builder->whereKey($category->id));
        }

        return view('admin.categories.products', [
            'category' => $category,
            'products' => $query->paginate(30)->withQueryString(),
            'filters' => [
                'q' => $search,
                'assignment' => $assignment,
            ],
        ]);
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
            'assignments' => ['nullable', 'array', 'max:100'],
            'assignments.*.assigned' => ['nullable', 'boolean'],
            'assignments.*.is_primary' => ['nullable', 'boolean'],
            'assignments.*.is_featured' => ['nullable', 'boolean'],
            'assignments.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $visibleIds = collect($validated['visible_product_ids'])->map(fn ($id) => (int) $id)->unique();
        $assignments = collect($validated['assignments'] ?? []);

        DB::transaction(function () use ($category, $visibleIds, $assignments): void {
            DB::table('category_product')->whereIn('product_id', $visibleIds)->lockForUpdate()->get();

            foreach ($visibleIds as $productId) {
                $input = $assignments->get((string) $productId, $assignments->get($productId, []));
                $assigned = is_array($input) && filter_var($input['assigned'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (! $assigned) {
                    $category->products()->detach($productId);
                } else {
                    $isPrimary = filter_var($input['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($isPrimary) {
                        DB::table('category_product')->where('product_id', $productId)->update(['is_primary' => false]);
                    }

                    $category->products()->syncWithoutDetaching([
                        $productId => [
                            'is_primary' => $isPrimary,
                            'is_featured' => filter_var($input['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'sort_order' => (int) ($input['sort_order'] ?? 0),
                        ],
                    ]);
                }

                $primary = DB::table('category_product')
                    ->where('product_id', $productId)
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('category_id')
                    ->first();

                if (! $primary) {
                    Product::query()->whereKey($productId)->update(['category_id' => null, 'subcategory_id' => null]);
                    continue;
                }

                if (! $primary->is_primary) {
                    DB::table('category_product')
                        ->where('product_id', $productId)
                        ->where('category_id', $primary->category_id)
                        ->update(['is_primary' => true]);
                }

                $primaryCategory = Category::query()->find($primary->category_id);
                if ($primaryCategory) {
                    $this->syncLegacyPrimaryCategory($productId, $primaryCategory);
                }
            }
        });

        $this->treeService->flushCache();

        return back()->with('status', 'Category product assignments updated.');
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
