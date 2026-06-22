<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductFormRequest;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Security\SafeHtmlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly SafeHtmlService $safeHtml,
        private readonly CategoryTreeService $categoryTreeService,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Product::query()->with(['category', 'subcategory', 'categories', 'images']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        return view('admin.products.index', [
            'products' => $query->latest()->paginate(25)->withQueryString(),
            'filters' => $request->only(['q', 'status', 'featured']),
        ]);
    }

    public function create(): View
    {
        $product = new Product([
            'status' => 'draft', 'currency' => 'USD', 'minimum_quantity' => 1,
            'is_active' => true, 'is_customizable' => true, 'robots_index' => true,
            'robots_follow' => true, 'low_stock_threshold' => 5,
        ]);

        return $this->formView('admin.products.create', $product);
    }

    public function store(ProductFormRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $product = Product::query()->create($this->productPayload($request, null));
            $this->syncRelations($product, $request);

            return $product;
        });

        $this->categoryTreeService->flushCache();

        return redirect()->route('admin.products.edit', $product)->with('status', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $product->load($this->relations());

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load($this->relations());

        return $this->formView('admin.products.edit', $product);
    }

    public function update(ProductFormRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $product->update($this->productPayload($request, $product));
            $this->syncRelations($product, $request);
        });

        $this->categoryTreeService->flushCache();

        return redirect()->route('admin.products.edit', $product)->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        $this->categoryTreeService->flushCache();

        return redirect()->route('admin.products.index')->with('status', 'Product moved to trash.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $product->load($this->relations());

        $copy = DB::transaction(function () use ($product): Product {
            $copy = $product->replicate();
            $copy->name = $product->name.' Copy';
            $copy->slug = Str::slug($copy->name).'-'.Str::lower(Str::random(5));
            $copy->sku = $product->sku.'-COPY-'.Str::upper(Str::random(4));
            $copy->status = 'draft';
            $copy->is_active = false;
            $copy->is_featured = false;
            $copy->published_at = null;
            $copy->created_by = auth()->id();
            $copy->updated_by = auth()->id();
            $copy->save();

            foreach ($product->images as $item) {
                $copy->images()->create(Arr::except($item->toArray(), ['id', 'product_id', 'created_at', 'updated_at']));
            }
            foreach ($product->optionGroups as $group) {
                $newGroup = $copy->optionGroups()->create(Arr::except($group->toArray(), ['id', 'product_id', 'created_at', 'updated_at']));
                foreach ($group->values as $value) {
                    $newGroup->values()->create(Arr::except($value->toArray(), ['id', 'product_option_group_id', 'created_at', 'updated_at']));
                }
            }
            foreach ($product->sizeGroups as $group) {
                $newGroup = $copy->sizeGroups()->create(Arr::except($group->toArray(), ['id', 'product_id', 'created_at', 'updated_at']));
                foreach ($group->sizes as $size) {
                    $newGroup->sizes()->create(Arr::except($size->toArray(), ['id', 'product_size_group_id', 'created_at', 'updated_at']));
                }
            }
            foreach (['priceTiers', 'artworkMethods', 'productionSpeeds', 'faqs'] as $relation) {
                foreach ($product->{$relation} as $item) {
                    $copy->{$relation}()->create(Arr::except($item->toArray(), ['id', 'product_id', 'created_at', 'updated_at']));
                }
            }
            $copy->categories()->sync($product->categories->mapWithKeys(fn ($category) => [$category->id => [
                'is_primary' => (bool) $category->pivot->is_primary,
                'is_featured' => (bool) $category->pivot->is_featured,
                'sort_order' => (int) $category->pivot->sort_order,
            ]])->all());
            $copy->attributeValues()->sync($product->attributeValues->pluck('id')->all());

            return $copy;
        });

        $this->categoryTreeService->flushCache();

        return redirect()->route('admin.products.edit', $copy)->with('status', 'Product duplicated as a draft.');
    }

    private function formView(string $view, Product $product): View
    {
        return view($view, [
            'product' => $product,
            'categoryOptions' => $this->categoryTreeService->flatOptions(),
            'catalogAttributes' => CatalogAttribute::query()->active()->with(['values' => fn ($query) => $query->active()->orderBy('sort_order')])->ordered()->get(),
        ]);
    }

    private function relations(): array
    {
        return [
            'category', 'subcategory', 'categories', 'attributeValues.attribute', 'images', 'optionGroups.values', 'sizeGroups.sizes',
            'priceTiers', 'artworkMethods', 'productionSpeeds', 'faqs',
        ];
    }

    private function productPayload(ProductFormRequest $request, ?Product $product): array
    {
        $data = $request->validated();
        $specifications = collect($data['specifications'] ?? [])
            ->filter(fn ($row) => filled($row['name'] ?? null) && filled($row['value'] ?? null))
            ->mapWithKeys(fn ($row) => [trim($row['name']) => trim($row['value'])])->all();

        $payload = Arr::only($data, [
            'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'status', 'product_type', 'brand',
            'badge_label', 'badge_color', 'short_description', 'base_price', 'compare_at_price',
            'cost_price', 'currency', 'minimum_quantity', 'maximum_quantity', 'is_featured',
            'is_customizable', 'is_active', 'track_inventory', 'stock_quantity', 'low_stock_threshold',
            'allow_backorder', 'weight', 'shipping_class', 'tax_class', 'price_table_highlight_column',
            'price_table_note', 'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
            'og_title', 'og_description', 'og_image_url', 'robots_index', 'robots_follow',
            'sort_order', 'published_at',
        ]);

        $primaryCategoryId = $data['primary_category_id'] ?? null;
        $primaryCategory = $primaryCategoryId ? Category::query()->find($primaryCategoryId) : null;
        $payload['category_id'] = $primaryCategory?->parent_id ?: $primaryCategory?->id;
        $payload['subcategory_id'] = $primaryCategory?->parent_id ? $primaryCategory->id : null;

        $payload['description_html'] = $this->safeHtml->sanitize($data['description_html'] ?? null);
        $payload['features'] = collect($data['features'] ?? [])->map(fn ($item) => trim((string) $item))->filter()->values()->all();
        $payload['specifications'] = $specifications;
        $payload['dimensions'] = array_filter([
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
        $payload['tags'] = collect(explode(',', (string) ($data['tags_text'] ?? '')))->map(fn ($tag) => trim($tag))->filter()->unique()->values()->all();
        $payload['price_table_headers'] = collect($data['price_table_headers'] ?? [])->map(fn ($value) => trim((string) $value))->filter()->values()->all();
        $headerCount = count($payload['price_table_headers']);
        $payload['price_table_rows'] = collect($data['price_table_rows'] ?? [])->map(function ($row) use ($headerCount) {
            $cells = collect($row)->take($headerCount ?: 20)->map(fn ($cell) => trim((string) $cell))->values()->all();
            return array_pad($cells, $headerCount, '');
        })->filter(fn ($row) => collect($row)->filter()->isNotEmpty())->values()->all();
        $payload['schema_json'] = filled($data['schema_json_text'] ?? null) ? json_decode($data['schema_json_text'], true, 512, JSON_THROW_ON_ERROR) : null;
        $payload['created_by'] = $product?->created_by ?? auth()->id();
        $payload['updated_by'] = auth()->id();

        return $payload;
    }

    private function syncRelations(Product $product, ProductFormRequest $request): void
    {
        $data = $request->validated();
        $this->syncCatalogAssignments($product, $data);
        $this->syncImages($product, $request, $data);

        $existingOptionImages = $product->optionGroups()
            ->with('values:id,product_option_group_id,image_path')
            ->get()
            ->flatMap(fn ($group) => $group->values)
            ->filter(fn ($value) => filled($value->image_path))
            ->mapWithKeys(fn ($value) => [(int) $value->id => $value->image_path])
            ->all();

        $product->optionGroups()->delete();
        $groupSortOrder = 0;

        foreach (collect($data['option_groups'] ?? [])->filter(fn ($group) => filled($group['name'] ?? null) && filled($group['code'] ?? null)) as $groupInputIndex => $groupData) {
            $group = $product->optionGroups()->create([
                'name' => $groupData['name'], 'code' => $groupData['code'], 'section' => $groupData['section'] ?? 'product',
                'type' => $groupData['type'] ?? 'select', 'description' => $groupData['description'] ?? null,
                'placeholder' => $groupData['placeholder'] ?? null, 'is_required' => (bool) ($groupData['is_required'] ?? false),
                'minimum_selections' => $groupData['minimum_selections'] ?? null, 'maximum_selections' => $groupData['maximum_selections'] ?? null,
                'accepted_file_types' => $groupData['accepted_file_types'] ?? null, 'maximum_file_size_mb' => $groupData['maximum_file_size_mb'] ?? null,
                'is_active' => (bool) ($groupData['is_active'] ?? true), 'sort_order' => $groupSortOrder++,
            ]);

            $valueSortOrder = 0;

            foreach (collect($groupData['values'] ?? [])->filter(fn ($value) => filled($value['label'] ?? null) && filled($value['code'] ?? null)) as $valueInputIndex => $valueData) {
                $uploadedImage = $request->file("option_groups.{$groupInputIndex}.values.{$valueInputIndex}.image_file");
                $imageUrl = filled($valueData['image_url'] ?? null) ? trim((string) $valueData['image_url']) : null;
                $existingId = (int) ($valueData['existing_id'] ?? 0);
                $imagePath = $imageUrl === null ? ($existingOptionImages[$existingId] ?? null) : null;

                if ($uploadedImage) {
                    $imagePath = $uploadedImage->store("products/{$product->id}/options", 'public');
                    $imageUrl = null;
                }

                $group->values()->create([
                    'label' => $valueData['label'], 'code' => $valueData['code'], 'description' => $valueData['description'] ?? null,
                    'color_hex' => $valueData['color_hex'] ?? null, 'image_path' => $imagePath, 'image_url' => $imageUrl,
                    'price_adjustment' => $valueData['price_adjustment'] ?? 0, 'stock_quantity' => $valueData['stock_quantity'] ?? null,
                    'is_default' => (bool) ($valueData['is_default'] ?? false), 'is_active' => (bool) ($valueData['is_active'] ?? true),
                    'sort_order' => $valueSortOrder++,
                ]);
            }
        }

        $product->sizeGroups()->delete();
        foreach (collect($data['size_groups'] ?? [])->filter(fn ($group) => filled($group['name'] ?? null) && filled($group['code'] ?? null))->values() as $index => $groupData) {
            $group = $product->sizeGroups()->create([
                'name' => $groupData['name'], 'code' => $groupData['code'], 'is_active' => (bool) ($groupData['is_active'] ?? true), 'sort_order' => $index,
            ]);
            $sizes = collect(preg_split('/[,\r\n]+/', (string) ($groupData['sizes_text'] ?? '')))
                ->map(fn ($size) => trim((string) $size))->filter()->unique()->values();
            foreach ($sizes as $sizeIndex => $size) {
                $group->sizes()->create(['label' => $size, 'code' => Str::slug($size), 'sort_order' => $sizeIndex, 'is_active' => true]);
            }
        }

        $this->replaceSimpleRelation($product, 'priceTiers', $data['price_tiers'] ?? [], ['minimum_quantity', 'unit_price'], [
            'label', 'minimum_quantity', 'maximum_quantity', 'unit_price', 'compare_at_price', 'savings_label',
        ]);
        $this->replaceSimpleRelation($product, 'artworkMethods', $data['artwork_methods'] ?? [], ['name', 'code'], [
            'name', 'code', 'icon', 'description', 'price_adjustment', 'requires_upload', 'is_active',
        ]);
        $this->replaceSimpleRelation($product, 'productionSpeeds', $data['production_speeds'] ?? [], ['name', 'code'], [
            'name', 'code', 'description', 'price_adjustment', 'minimum_days', 'maximum_days', 'is_active',
        ]);
        $this->replaceSimpleRelation($product, 'faqs', $data['faqs'] ?? [], ['question', 'answer'], ['question', 'answer', 'is_active']);
    }

    private function syncCatalogAssignments(Product $product, array $data): void
    {
        $primaryId = isset($data['primary_category_id']) ? (int) $data['primary_category_id'] : null;
        $categoryIds = collect($data['category_assignments'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($primaryId) {
            $categoryIds = $categoryIds->prepend($primaryId)->unique()->values();
        } elseif ($categoryIds->isNotEmpty()) {
            $primaryId = (int) $categoryIds->first();
        }

        // Product editing must not erase category-specific merchandising choices
        // managed from the category product-assignment screen. Preserve existing
        // featured flags and ordering for retained category assignments.
        $existing = DB::table('category_product')
            ->where('product_id', $product->id)
            ->get()
            ->keyBy('category_id');

        $sync = $categoryIds->mapWithKeys(function (int $categoryId, int $index) use ($existing, $primaryId): array {
            $pivot = $existing->get($categoryId);

            return [$categoryId => [
                'is_primary' => $categoryId === $primaryId,
                'is_featured' => (bool) ($pivot->is_featured ?? false),
                'sort_order' => (int) ($pivot->sort_order ?? $index),
            ]];
        })->all();

        $product->categories()->sync($sync);
        $product->attributeValues()->sync(
            collect($data['attribute_value_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all()
        );
    }

    private function syncImages(Product $product, ProductFormRequest $request, array $data): void
    {
        $product->images()->delete();
        $primaryAssigned = false;
        foreach (collect($data['image_urls'] ?? [])->filter(fn ($item) => filled($item['url'] ?? null))->values() as $index => $item) {
            $primary = ! $primaryAssigned && ((bool) ($item['is_primary'] ?? false) || $index === 0);
            $product->images()->create(['url' => $item['url'], 'alt_text' => $item['alt'] ?? $product->name, 'is_primary' => $primary, 'sort_order' => $index]);
            $primaryAssigned = $primaryAssigned || $primary;
        }

        foreach ($request->file('images', []) as $index => $image) {
            $path = $image->store("products/{$product->id}", 'public');
            $product->images()->create([
                'path' => $path, 'alt_text' => $product->name, 'is_primary' => ! $primaryAssigned,
                'sort_order' => count($data['image_urls'] ?? []) + $index,
            ]);
            $primaryAssigned = true;
        }
    }

    private function replaceSimpleRelation(Product $product, string $relation, array $rows, array $required, array $columns): void
    {
        $product->{$relation}()->delete();
        foreach (collect($rows)->filter(function ($row) use ($required) {
            foreach ($required as $key) {
                if (! filled($row[$key] ?? null)) {
                    return false;
                }
            }
            return true;
        })->values() as $index => $row) {
            $payload = Arr::only($row, $columns);
            foreach (['requires_upload', 'is_active'] as $boolean) {
                if (array_key_exists($boolean, $payload)) {
                    $payload[$boolean] = (bool) $payload[$boolean];
                }
            }
            $payload['sort_order'] = $index;
            $product->{$relation}()->create($payload);
        }
    }
}
