<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryFormRequest;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\UrlRedirect;
use App\Services\Catalog\CategoryMediaService;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\NavigationService;
use App\Services\Security\SafeHtmlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryTreeService $treeService,
        private readonly CategoryMediaService $mediaService,
        private readonly NavigationService $navigationService,
        private readonly SafeHtmlService $safeHtml,
    ) {
    }

    public function index(Request $request): View
    {
        $allowedStatuses = ['draft', 'active', 'inactive', 'archived'];
        $allowedTypes = ['standard', 'sport', 'collection', 'apparel', 'accessory', 'promotional', 'sale', 'new-arrival', 'navigation-only'];

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            'status' => in_array($request->query('status'), $allowedStatuses, true) ? (string) $request->query('status') : '',
            'type' => in_array($request->query('type'), $allowedTypes, true) ? (string) $request->query('type') : '',
            'empty' => $request->boolean('empty'),
        ];

        $query = Category::query()
            ->select([
                'id', 'parent_id', 'name', 'menu_label', 'slug', 'category_type', 'page_template',
                'status', 'depth', 'tree_path', 'is_active', 'is_visible_in_catalog', 'is_visible_in_menu',
                'is_featured', 'sort_order', 'updated_at', 'updated_by',
            ])
            ->with(['parent:id,name', 'updater:id,name'])
            ->withCount(['children', 'products']);

        if ($filters['q'] !== '') {
            $search = addcslashes($filters['q'], '\\%_');
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('menu_label', 'like', "%{$search}%");
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['type'] !== '') {
            $query->where('category_type', $filters['type']);
        }

        if ($filters['empty']) {
            $query->doesntHave('products');
        }

        return view('admin.categories.index', [
            'categories' => $query->orderBy('tree_path')->orderBy('sort_order')->orderBy('name')->paginate(40)->withQueryString(),
            'filters' => $filters,
            'analytics' => [
                'total' => Category::query()->count(),
                'active' => Category::query()->where('status', 'active')->where('is_active', true)->count(),
                'featured' => Category::query()->where('is_featured', true)->count(),
                'empty' => Category::query()->doesntHave('products')->count(),
                'max_depth' => (int) Category::query()->max('depth'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $parentId = $request->integer('parent_id');
        $parentId = $parentId && Category::query()->whereKey($parentId)->exists() ? $parentId : null;

        $category = new Category([
            'parent_id' => $parentId,
            'category_type' => 'standard',
            'page_template' => 'product_grid',
            'status' => 'draft',
            'cta_label' => 'View Category',
            'is_active' => false,
            'is_visible_in_catalog' => true,
            'is_visible_in_menu' => true,
            'show_product_count' => true,
            'include_descendant_products' => true,
            'robots_index' => true,
            'robots_follow' => true,
            'default_product_sort' => 'featured',
        ]);

        return $this->formView('admin.categories.create', $category);
    }

    public function store(CategoryFormRequest $request): RedirectResponse
    {
        $category = DB::transaction(function () use ($request): Category {
            $payload = $this->payload($request, null);
            $this->treeService->assertValidParent(new Category(), $payload['parent_id'] ?? null);
            $category = Category::query()->create($payload);
            $this->mediaService->sync($category, $request);
            $this->syncFilters($category, $request->validated('filter_settings', []));
            $this->syncContentBlocks($category, $request);
            $this->syncFaqs($category, $request->validated('faqs', []));
            $this->treeService->rebuildClosure();

            return $category;
        });

        $this->navigationService->flushCache();

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $category->load(['filters', 'contentBlocks', 'faqs']);

        return $this->formView('admin.categories.edit', $category);
    }

    public function update(CategoryFormRequest $request, Category $category): RedirectResponse
    {
        DB::transaction(function () use ($request, $category): void {
            $oldSlug = $category->slug;
            $payload = $this->payload($request, $category);
            $this->treeService->assertValidParent($category, $payload['parent_id'] ?? null);
            $category->update($payload);
            $this->mediaService->sync($category, $request);
            $this->syncFilters($category, $request->validated('filter_settings', []));
            $this->syncContentBlocks($category, $request);
            $this->syncFaqs($category, $request->validated('faqs', []));

            if ($oldSlug !== $category->slug) {
                UrlRedirect::query()->updateOrCreate(
                    ['old_path' => '/category/'.$oldSlug],
                    [
                        'new_path' => '/category/'.$category->slug,
                        'status_code' => 301,
                        'is_active' => true,
                        'redirectable_type' => Category::class,
                        'redirectable_id' => $category->id,
                    ]
                );
            }

            $this->treeService->rebuildClosure();
            $this->navigationService->flushCache();
        });

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Category updated successfully.');
    }

    public function duplicate(Category $category): RedirectResponse
    {
        $category->load(['filters', 'contentBlocks', 'faqs']);

        $copy = DB::transaction(function () use ($category): Category {
            $copy = $category->replicate([
                'slug', 'status', 'is_active', 'is_featured', 'published_at',
                'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
            ]);
            $copy->name = Str::limit($category->name.' Copy', 160, '');
            $baseSlug = Str::slug($copy->name);
            do {
                $copy->slug = Str::limit($baseSlug.'-'.Str::lower(Str::random(5)), 180, '');
            } while (Category::withTrashed()->where('slug', $copy->slug)->exists());
            $copy->status = 'draft';
            $copy->is_active = false;
            $copy->is_featured = false;
            $copy->published_at = null;
            $copy->created_by = auth()->id();
            $copy->updated_by = auth()->id();
            $copy->save();

            $this->mediaService->duplicate($category, $copy);
            $copy->filters()->sync($category->filters->mapWithKeys(fn ($attribute) => [
                $attribute->id => [
                    'label' => $attribute->pivot->label,
                    'is_expanded' => (bool) $attribute->pivot->is_expanded,
                    'sort_order' => (int) $attribute->pivot->sort_order,
                ],
            ])->all());

            foreach ($category->contentBlocks as $block) {
                $payload = Arr::except($block->toArray(), ['id', 'category_id', 'created_at', 'updated_at']);
                $payload['image_path'] = $this->mediaService->copyPath(
                    $block->image_path,
                    "categories/{$copy->id}/blocks"
                );
                $copy->contentBlocks()->create($payload);
            }

            foreach ($category->faqs as $faq) {
                $copy->faqs()->create(Arr::except($faq->toArray(), ['id', 'category_id', 'created_at', 'updated_at']));
            }

            $this->treeService->rebuildClosure();

            return $copy;
        });

        $this->navigationService->flushCache();

        return redirect()->route('admin.categories.edit', $copy)
            ->with('status', 'Category duplicated as a draft. Product assignments and child categories were intentionally not copied.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->withErrors(['category' => 'Move or archive the child categories before deleting this category.']);
        }

        if ($category->products()->exists() || $category->legacyProducts()->exists() || $category->subcategoryProducts()->exists()) {
            return back()->withErrors(['category' => 'This category is assigned to products. Remove those assignments before deleting it.']);
        }

        if (MenuItem::query()->where('category_id', $category->id)->exists()) {
            return back()->withErrors(['category' => 'This category is used by a navigation menu. Remove the menu links first.']);
        }

        DB::transaction(function () use ($category): void {
            $this->mediaService->deleteAll($category);
            $category->delete();
            $this->treeService->rebuildClosure();
            $this->navigationService->flushCache();
        });

        return redirect()->route('admin.categories.index')->with('status', 'Category moved to trash.');
    }

    private function formView(string $view, Category $category): View
    {
        return view($view, [
            'category' => $category,
            'parents' => $this->treeService->flatOptions($category->exists ? $category->id : null),
            'attributes' => CatalogAttribute::query()->with('values')->ordered()->get(),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(CategoryFormRequest $request, ?Category $category): array
    {
        $data = $request->validated();
        $plainDescription = trim((string) ($data['description'] ?? ''));
        $sanitizedHtml = $this->safeHtml->sanitize($data['description_html'] ?? null);

        if ($plainDescription === '') {
            $plainDescription = trim(strip_tags((string) $sanitizedHtml));
        }
        if ($plainDescription === '') {
            $plainDescription = trim((string) ($data['short_description'] ?? $data['name']));
        }

        $payload = Arr::only($data, [
            'parent_id', 'name', 'menu_label', 'slug', 'category_type', 'page_template', 'status',
            'eyebrow', 'short_title', 'short_description', 'best_for', 'cta_label', 'icon', 'sort_order',
            'published_at', 'default_product_sort', 'image_alt', 'thumbnail_alt', 'banner_alt',
            'mobile_banner_alt', 'is_visible_in_catalog', 'is_visible_in_menu', 'is_featured',
            'show_product_count', 'include_descendant_products', 'meta_title', 'meta_description',
            'meta_keywords', 'canonical_url', 'og_title', 'og_description', 'robots_index', 'robots_follow',
        ]);

        $payload['slug'] = $this->uniqueSlug(
            trim((string) ($data['slug'] ?? '')) ?: (string) $data['name'],
            $category?->id,
        );
        $payload['description'] = Str::limit($plainDescription, 10000, '');
        $payload['description_html'] = $sanitizedHtml;
        $payload['display_type'] = $data['category_type'] === 'sport' ? 'sport' : 'collection';
        $payload['is_active'] = $data['status'] === 'active';
        $payload['image_url'] = $category?->image_url ?? (string) ($data['image_url'] ?? '');
        $payload['image_alt'] = filled($data['image_alt'] ?? null) ? $data['image_alt'] : $data['name'];
        $payload['thumbnail_alt'] = filled($data['thumbnail_alt'] ?? null) ? $data['thumbnail_alt'] : $data['name'];
        $payload['highlights'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['highlights_text'] ?? '')))
            ->map(fn ($value) => trim((string) $value))->filter()->values()->all();
        $payload['schema_json'] = filled($data['schema_json_text'] ?? null)
            ? json_decode($data['schema_json_text'], true, 512, JSON_THROW_ON_ERROR)
            : null;
        $payload['created_by'] = $category?->created_by ?? auth()->id();
        $payload['updated_by'] = auth()->id();

        return $payload;
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($value) ?: 'category', 170, '');
        $slug = $base;
        $suffix = 2;

        while (Category::withTrashed()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $ending = '-'.$suffix++;
            $slug = Str::limit($base, 180 - strlen($ending), '').$ending;
        }

        return $slug;
    }

    /** @param array<int|string, mixed> $settings */
    private function syncFilters(Category $category, array $settings): void
    {
        $sync = [];
        foreach ($settings as $attributeId => $setting) {
            if (! is_array($setting) || ! ($setting['enabled'] ?? false)) {
                continue;
            }
            $sync[(int) $attributeId] = [
                'label' => filled($setting['label'] ?? null) ? trim((string) $setting['label']) : null,
                'is_expanded' => (bool) ($setting['is_expanded'] ?? true),
                'sort_order' => (int) ($setting['sort_order'] ?? 0),
            ];
        }
        $category->filters()->sync($sync);
    }

    private function syncContentBlocks(Category $category, CategoryFormRequest $request): void
    {
        $existingPaths = $category->contentBlocks()->pluck('image_path', 'id')->filter()->all();
        $retainedPaths = [];
        $category->contentBlocks()->delete();

        $sortOrder = 0;
        foreach ($request->validated('content_blocks', []) as $index => $block) {
            if (! filled($block['heading'] ?? null)
                && ! filled($block['content_html'] ?? null)
                && ! filled($block['image_url'] ?? null)
                && ! $request->hasFile("content_blocks.{$index}.image_file")
                && ! filled($block['button_label'] ?? null)) {
                continue;
            }
            $existingId = (int) ($block['existing_id'] ?? 0);
            $imagePath = $existingPaths[$existingId] ?? null;
            $imageUrl = filled($block['image_url'] ?? null) ? trim((string) $block['image_url']) : null;
            $uploaded = $request->file("content_blocks.{$index}.image_file");

            if ($uploaded) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $uploaded->store("categories/{$category->id}/blocks", 'public');
                $imageUrl = null;
            } elseif ($imageUrl !== null) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = null;
            }

            if ($imagePath) {
                $retainedPaths[] = $imagePath;
            }

            $category->contentBlocks()->create([
                'block_type' => $block['block_type'] ?? 'rich_text',
                'heading' => $block['heading'] ?? null,
                'subheading' => $block['subheading'] ?? null,
                'content_html' => $this->safeHtml->sanitize($block['content_html'] ?? null),
                'image_path' => $imagePath,
                'image_url' => $imageUrl,
                'image_alt' => $block['image_alt'] ?? null,
                'button_label' => $block['button_label'] ?? null,
                'button_url' => $block['button_url'] ?? null,
                'settings' => filled($block['settings_json'] ?? null)
                    ? json_decode($block['settings_json'], true, 512, JSON_THROW_ON_ERROR)
                    : null,
                'is_active' => (bool) ($block['is_active'] ?? true),
                'sort_order' => $sortOrder++,
            ]);
        }

        foreach (array_diff($existingPaths, $retainedPaths) as $path) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @param array<int, mixed> $faqs */
    private function syncFaqs(Category $category, array $faqs): void
    {
        $category->faqs()->delete();
        foreach (collect($faqs)->filter(fn ($faq) => filled($faq['question'] ?? null) && filled($faq['answer_html'] ?? null))->values() as $index => $faq) {
            $category->faqs()->create([
                'question' => trim((string) $faq['question']),
                'answer_html' => $this->safeHtml->sanitize($faq['answer_html']) ?: '',
                'is_active' => (bool) ($faq['is_active'] ?? true),
                'sort_order' => $index,
            ]);
        }
    }
}
