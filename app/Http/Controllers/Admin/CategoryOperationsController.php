<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryBulkRequest;
use App\Http\Requests\Admin\CategoryImportRequest;
use App\Http\Requests\Admin\CategoryReorderRequest;
use App\Models\Category;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\NavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryOperationsController extends Controller
{
    private const TYPES = ['standard', 'sport', 'collection', 'apparel', 'accessory', 'promotional', 'sale', 'new-arrival', 'navigation-only'];
    private const TEMPLATES = ['product_grid', 'sport_landing', 'collection_landing', 'image_focused', 'quote_only', 'content_landing', 'navigation_only'];
    private const STATUSES = ['draft', 'active', 'inactive', 'archived'];

    public function __construct(
        private readonly CategoryTreeService $treeService,
        private readonly NavigationService $navigationService,
    ) {
    }

    public function bulk(CategoryBulkRequest $request): RedirectResponse
    {
        $ids = collect($request->validated('category_ids'))->map(fn ($id) => (int) $id)->unique()->values();
        $action = $request->validated('action');

        $payload = match ($action) {
            'activate' => ['status' => 'active', 'is_active' => true],
            'deactivate' => ['status' => 'inactive', 'is_active' => false],
            'archive' => ['status' => 'archived', 'is_active' => false],
            'feature' => ['is_featured' => true],
            'unfeature' => ['is_featured' => false],
            'show_in_menu' => ['is_visible_in_menu' => true],
            'hide_from_menu' => ['is_visible_in_menu' => false],
        };

        $updated = DB::transaction(function () use ($ids, $payload): int {
            return Category::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->update(array_merge($payload, [
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]));
        });

        $this->treeService->flushCache();
        $this->navigationService->flushCache();

        return back()->with('status', "{$updated} category records updated.");
    }

    public function ordering(): View
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.ordering', [
            'groups' => $categories->groupBy(fn (Category $category) => $category->parent_id ?: 0),
            'parentLabels' => $categories->pluck('name', 'id'),
        ]);
    }

    public function updateOrdering(CategoryReorderRequest $request): RedirectResponse
    {
        $positions = collect($request->validated('positions'))
            ->map(fn (array $position) => [
                'id' => (int) $position['id'],
                'parent_id' => isset($position['parent_id']) ? (int) $position['parent_id'] : null,
                'sort_order' => (int) $position['sort_order'],
            ]);

        DB::transaction(function () use ($positions): void {
            // Lock the complete hierarchy so a concurrent move cannot introduce a
            // cycle between validation and persistence.
            $models = Category::query()
                ->lockForUpdate()
                ->get(['id', 'parent_id', 'sort_order'])
                ->keyBy('id');

            $parentMap = $models->mapWithKeys(fn (Category $category): array => [
                (int) $category->id => $category->parent_id ? (int) $category->parent_id : null,
            ])->all();

            foreach ($positions as $position) {
                if ($models->has($position['id'])) {
                    $parentMap[$position['id']] = $position['parent_id'];
                }
            }

            $this->assertValidHierarchy($parentMap);

            foreach ($positions as $position) {
                $category = $models->get($position['id']);
                if (! $category) {
                    continue;
                }

                $category->forceFill([
                    'parent_id' => $position['parent_id'],
                    'sort_order' => $position['sort_order'],
                    'updated_by' => auth()->id(),
                ])->save();
            }

            $this->treeService->rebuildClosure();
        });

        $this->navigationService->flushCache();

        return redirect()->route('admin.categories.ordering')->with('status', 'Category order updated successfully.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'nextplay-categories-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, [
                'name', 'slug', 'parent_slug', 'menu_label', 'category_type', 'page_template', 'status',
                'short_description', 'sort_order', 'is_featured', 'is_visible_in_catalog',
                'is_visible_in_menu', 'include_descendant_products', 'meta_title', 'meta_description',
            ]);

            Category::query()
                ->with('parent:id,slug')
                ->orderBy('tree_path')
                ->orderBy('sort_order')
                ->chunk(250, function ($categories) use ($output): void {
                    foreach ($categories as $category) {
                        fputcsv($output, array_map([$this, 'safeCsvCell'], [
                            $category->name,
                            $category->slug,
                            $category->parent?->slug,
                            $category->menu_label,
                            $category->category_type,
                            $category->page_template,
                            $category->status,
                            $category->short_description,
                            $category->sort_order,
                            $category->is_featured ? 1 : 0,
                            $category->is_visible_in_catalog ? 1 : 0,
                            $category->is_visible_in_menu ? 1 : 0,
                            $category->include_descendant_products ? 1 : 0,
                            $category->meta_title,
                            $category->meta_description,
                        ]));
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(CategoryImportRequest $request): RedirectResponse
    {
        $records = $this->readCsv($request->file('category_csv')->getRealPath());
        if ($records === []) {
            throw ValidationException::withMessages(['category_csv' => 'The CSV file does not contain any category rows.']);
        }

        DB::transaction(function () use ($records): void {
            $seen = [];
            $parentSlugs = [];

            foreach ($records as $line => $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $slug = Str::slug((string) ($row['slug'] ?? $name));
                if ($name === '' || $slug === '') {
                    throw ValidationException::withMessages(['category_csv' => "Line {$line}: name and slug are required."]);
                }
                if (isset($seen[$slug])) {
                    throw ValidationException::withMessages(['category_csv' => "Line {$line}: duplicate slug '{$slug}' in the import file."]);
                }
                $seen[$slug] = true;

                $type = $this->enumValue($row['category_type'] ?? null, self::TYPES, 'standard', $line, 'category_type');
                $template = $this->enumValue($row['page_template'] ?? null, self::TEMPLATES, 'product_grid', $line, 'page_template');
                $status = $this->enumValue($row['status'] ?? null, self::STATUSES, 'draft', $line, 'status');

                $category = Category::query()->firstOrNew(['slug' => $slug]);
                $category->forceFill([
                    'name' => Str::limit($name, 160, ''),
                    'menu_label' => $this->nullableLimited($row['menu_label'] ?? null, 160),
                    'display_type' => $type === 'sport' ? 'sport' : 'collection',
                    'category_type' => $type,
                    'page_template' => $template,
                    'status' => $status,
                    'is_active' => $status === 'active',
                    'short_description' => $this->nullableLimited($row['short_description'] ?? null, 1500),
                    'description' => $category->description ?: $this->nullableLimited($row['short_description'] ?? null, 10000) ?: $name,
                    // These columns existed as NOT NULL fields before the dynamic
                    // category upgrade. Empty values intentionally fall back to the
                    // generated storefront placeholder and accessible category name.
                    'image_url' => $category->image_url ?? '',
                    'image_alt' => $category->image_alt ?: $name,
                    'cta_label' => $category->cta_label ?: 'View Category',
                    'sort_order' => $this->integerValue($row['sort_order'] ?? 0, 0, 1000000),
                    'is_featured' => $this->booleanValue($row['is_featured'] ?? null, false),
                    'is_visible_in_catalog' => $this->booleanValue($row['is_visible_in_catalog'] ?? null, true),
                    'is_visible_in_menu' => $this->booleanValue($row['is_visible_in_menu'] ?? null, true),
                    'include_descendant_products' => $this->booleanValue($row['include_descendant_products'] ?? null, true),
                    'show_product_count' => $category->exists ? $category->show_product_count : true,
                    'default_product_sort' => $category->default_product_sort ?: 'featured',
                    'robots_index' => $category->exists ? $category->robots_index : true,
                    'robots_follow' => $category->exists ? $category->robots_follow : true,
                    'meta_title' => $this->nullableLimited($row['meta_title'] ?? null, 255),
                    'meta_description' => $this->nullableLimited($row['meta_description'] ?? null, 1000),
                    'created_by' => $category->created_by ?: auth()->id(),
                    'updated_by' => auth()->id(),
                ])->save();

                $parentSlugs[$category->id] = filled($row['parent_slug'] ?? null)
                    ? Str::slug((string) $row['parent_slug'])
                    : null;
            }

            $slugMap = Category::query()->pluck('id', 'slug');
            foreach ($parentSlugs as $categoryId => $parentSlug) {
                $category = Category::query()->lockForUpdate()->findOrFail($categoryId);
                $parentId = $parentSlug ? (int) ($slugMap[$parentSlug] ?? 0) : null;
                if ($parentSlug && ! $parentId) {
                    throw ValidationException::withMessages([
                        'category_csv' => "Parent category '{$parentSlug}' does not exist.",
                    ]);
                }
                $this->treeService->assertValidParent($category, $parentId ?: null);
                $category->update(['parent_id' => $parentId ?: null]);
            }

            $this->treeService->rebuildClosure();
        });

        $this->navigationService->flushCache();

        return redirect()->route('admin.categories.index')->with('status', count($records).' categories imported or updated.');
    }

    /** @return array<int, array<string, string|null>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw ValidationException::withMessages(['category_csv' => 'The uploaded CSV could not be read.']);
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            return [];
        }

        $header = array_map(function ($value): string {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value) ?? (string) $value;
            return Str::snake(trim($value));
        }, $header);

        foreach (['name', 'slug'] as $required) {
            if (! in_array($required, $header, true)) {
                fclose($handle);
                throw ValidationException::withMessages(['category_csv' => "The CSV must contain a '{$required}' column."]);
            }
        }

        $records = [];
        $line = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count($records) >= 1000) {
                fclose($handle);
                throw ValidationException::withMessages(['category_csv' => 'A maximum of 1,000 category rows can be imported at once.']);
            }
            $row = array_pad($row, count($header), null);
            $record = array_combine($header, array_slice($row, 0, count($header)));
            if (! is_array($record) || collect($record)->filter(fn ($value) => filled($value))->isEmpty()) {
                continue;
            }
            $records[$line] = $record;
        }
        fclose($handle);

        return $records;
    }

    private function enumValue(mixed $value, array $allowed, string $default, int $line, string $field): string
    {
        $value = filled($value) ? Str::lower(trim((string) $value)) : $default;
        if (! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages(['category_csv' => "Line {$line}: invalid {$field} value '{$value}'."]);
        }
        return $value;
    }

    private function booleanValue(mixed $value, bool $default = false): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ?? in_array(Str::lower(trim((string) $value)), ['1', 'yes', 'on'], true);
    }

    /** @param array<int, int|null> $parentMap */
    private function assertValidHierarchy(array $parentMap): void
    {
        $maximumDepth = (int) config('catalog.max_category_depth', 4);

        foreach (array_keys($parentMap) as $categoryId) {
            $seen = [];
            $current = (int) $categoryId;
            $depth = 0;

            while (($parentId = $parentMap[$current] ?? null) !== null) {
                if (! array_key_exists($parentId, $parentMap)) {
                    throw ValidationException::withMessages([
                        'positions' => "Category {$current} references a parent that does not exist.",
                    ]);
                }
                if ($parentId === (int) $categoryId || isset($seen[$parentId])) {
                    throw ValidationException::withMessages([
                        'positions' => 'Circular category relationships are not allowed.',
                    ]);
                }

                $seen[$parentId] = true;
                $current = $parentId;
                $depth++;

                if ($depth > $maximumDepth) {
                    throw ValidationException::withMessages([
                        'positions' => "The category hierarchy cannot exceed {$maximumDepth} levels.",
                    ]);
                }
            }
        }
    }

    private function integerValue(mixed $value, int $minimum, int $maximum): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false) {
            return $minimum;
        }
        return max($minimum, min($maximum, $number));
    }

    private function nullableLimited(mixed $value, int $length): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : Str::limit($value, $length, '');
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
