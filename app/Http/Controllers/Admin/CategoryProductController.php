<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryProductController extends Controller
{
    public function __construct(private readonly CategoryTreeService $treeService)
    {
    }

    public function index(Request $request, Category $category): View
    {
        $query = Product::query()
            ->with(['categories' => fn ($builder) => $builder->select('categories.id', 'categories.name')])
            ->with(['images'])
            ->orderBy('name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->query('assignment') === 'assigned') {
            $query->whereHas('categories', fn ($builder) => $builder->whereKey($category->id));
        } elseif ($request->query('assignment') === 'unassigned') {
            $query->whereDoesntHave('categories', fn ($builder) => $builder->whereKey($category->id));
        }

        return view('admin.categories.products', [
            'category' => $category,
            'products' => $query->paginate(30)->withQueryString(),
            'filters' => $request->only(['q', 'assignment']),
        ]);
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
