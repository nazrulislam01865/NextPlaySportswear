<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JerseyCustomizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductFormRequest;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\JerseyCustomizationOption;
use App\Models\Product;
use App\Models\SizeOptionGroup;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\ProductOptionFilterSyncService;
use App\Services\Security\SafeHtmlService;
use App\Support\ProductionTime;
use App\Support\PublicMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private readonly SafeHtmlService $safeHtml,
        private readonly CategoryTreeService $categoryTreeService,
        private readonly ProductOptionFilterSyncService $productOptionFilterSyncService,
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
            'status' => 'draft', 'currency' => 'USD', 'minimum_quantity' => 1, 'product_profile' => 'standard',
            'price_table_highlight_column' => 1,
            'is_active' => true, 'is_customizable' => true, 'robots_index' => true,
            'robots_follow' => true, 'low_stock_threshold' => 5,
            'artwork_upload_enabled' => false, 'artwork_upload_required' => false,
            'artwork_upload_title' => 'Upload Custom Artwork',
            'artwork_upload_description' => 'Upload one or more artwork files for the production team.',
            'artwork_upload_max_files' => 5, 'artwork_upload_max_file_size_mb' => 15,
            'artwork_upload_accepted_types' => 'pdf,svg,png,jpg,jpeg,webp',
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
            foreach (['priceTiers', 'productionSpeeds', 'shippingMethods', 'faqs'] as $relation) {
                foreach ($product->{$relation} as $item) {
                    $copy->{$relation}()->create(Arr::except($item->toArray(), ['id', 'product_id', 'created_at', 'updated_at']));
                }
            }
            $copy->categories()->sync($product->categories->mapWithKeys(fn ($category) => [$category->id => [
                'is_primary' => (bool) $category->pivot->is_primary,
                'is_featured' => (bool) $category->pivot->is_featured,
                'sort_order' => (int) $category->pivot->sort_order,
            ]])->all());
            $manualAttributeValueIds = $product->attributeValues->pluck('id')->all();
            $copy->attributeValues()->sync($manualAttributeValueIds);
            $this->productOptionFilterSyncService->sync($copy, $manualAttributeValueIds);

            return $copy;
        });

        $this->categoryTreeService->flushCache();

        return redirect()->route('admin.products.edit', $copy)->with('status', 'Product duplicated as a draft.');
    }

    private function formView(string $view, Product $product): View
    {
        $jerseyCustomizationOptions = JerseyCustomizationOption::query()
            ->active()
            ->with('images')
            ->ordered()
            ->get()
            ->map(static fn (JerseyCustomizationOption $option): array => [
                'id' => $option->id,
                'type' => $option->type->value,
                'type_label' => $option->type->label(),
                'name' => $option->name,
                'slug' => $option->slug,
                'description' => $option->description,
                'color_hex' => $option->color_hex,
                'images' => $option->images
                    ->map(static fn ($image): array => [
                        'name' => $image->name,
                        'url' => $image->publicUrl(),
                        'is_primary' => (bool) $image->is_primary,
                    ])
                    ->filter(static fn (array $image): bool => filled($image['url']))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $sizeOptionGroups = SizeOptionGroup::query()
            ->active()
            ->with(['sizes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->ordered()
            ->get()
            ->map(static fn (SizeOptionGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'audience' => $group->audience->value,
                'audience_label' => $group->audience->label(),
                'description_html' => $group->description_html,
                'sizes' => $group->sizes->pluck('label')->values()->all(),
                'chart_enabled' => filled($group->chart_html) || filled($group->chartImageUrl()),
                'chart_html' => $group->chart_html,
                'chart_title' => $group->chart_title,
                'chart_note' => $group->chart_note,
                'chart_columns' => $group->chart_columns ?? [],
                'chart_rows' => $group->chart_rows ?? [],
                'chart_image_preview' => $group->chartImageUrl(),
            ])
            ->values()
            ->all();

        return view($view, [
            'product' => $product,
            'categoryOptions' => $this->categoryTreeService->flatOptions(),
            'catalogAttributes' => CatalogAttribute::query()->active()->with(['values' => fn ($query) => $query->active()->orderBy('sort_order')])->ordered()->get(),
            'jerseyCustomizationTypes' => JerseyCustomizationType::options(),
            'jerseyCustomizationOptions' => $jerseyCustomizationOptions,
            'sizeOptionGroups' => $sizeOptionGroups,
        ]);
    }

    private function relations(): array
    {
        return [
            'category', 'subcategory', 'categories', 'attributeValues.attribute', 'images', 'optionGroups.values', 'sizeGroups.sizes', 'sizeGroups.masterGroup',
            'priceTiers', 'artworkMethods', 'productionSpeeds', 'shippingMethods', 'faqs',
        ];
    }

    private function productPayload(ProductFormRequest $request, ?Product $product): array
    {
        $data = $request->validated();

        $payload = Arr::only($data, [
            'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'status', 'product_type', 'product_profile', 'brand',
            'badge_label', 'badge_color', 'short_description', 'base_price', 'compare_at_price',
            'cost_price', 'currency', 'minimum_quantity', 'maximum_quantity', 'is_featured',
            'is_customizable', 'is_active', 'track_inventory', 'stock_quantity', 'low_stock_threshold',
            'allow_backorder', 'weight', 'shipping_class', 'shipping_methods_enabled', 'jersey_roster_enabled',
            'jersey_roster_optional', 'jersey_roster_title', 'artwork_upload_enabled',
            'artwork_upload_required', 'artwork_upload_title', 'artwork_upload_description',
            'artwork_upload_max_files', 'artwork_upload_max_file_size_mb',
            'artwork_upload_accepted_types', 'tax_class', 'price_table_highlight_column',
            'price_table_note', 'production_table_headers', 'production_table_rows', 'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
            'og_title', 'og_description', 'og_image_url', 'robots_index', 'robots_follow',
            'sort_order', 'published_at',
        ]);

        $primaryCategoryId = $data['primary_category_id'] ?? null;
        $primaryCategory = $primaryCategoryId ? Category::query()->find($primaryCategoryId) : null;
        $payload['category_id'] = $primaryCategory?->parent_id ?: $primaryCategory?->id;
        $payload['subcategory_id'] = $primaryCategory?->parent_id ? $primaryCategory->id : null;

        $payload['description_html'] = $this->safeHtml->sanitize($data['description_html'] ?? null);
        $payload['detail_information_html'] = $this->safeHtml->sanitize($data['detail_information_html'] ?? null);

        // The storefront-aligned editor intentionally omits legacy fields that are
        // not rendered on the product page. Preserve their existing values during
        // an update instead of silently clearing them.
        if ($request->exists('features')) {
            $payload['features'] = collect($data['features'] ?? [])->map(fn ($item) => trim((string) $item))->filter()->values()->all();
        } elseif ($product === null) {
            $payload['features'] = [];
        }

        if ($product === null) {
            $payload['specifications'] = [];
        }

        if ($request->exists('length') || $request->exists('width') || $request->exists('height')) {
            $payload['dimensions'] = array_filter([
                'length' => $data['length'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        } elseif ($product === null) {
            $payload['dimensions'] = [];
        }

        $payload['tags'] = collect(explode(',', (string) ($data['tags_text'] ?? '')))->map(fn ($tag) => trim($tag))->filter()->unique()->values()->all();
        if (! (bool) ($payload['artwork_upload_enabled'] ?? false)) {
            $payload['artwork_upload_required'] = false;
        }
        $payload['artwork_upload_title'] = filled($data['artwork_upload_title'] ?? null)
            ? trim((string) $data['artwork_upload_title'])
            : 'Upload Custom Artwork';
        $payload['artwork_upload_description'] = filled($data['artwork_upload_description'] ?? null)
            ? trim((string) $data['artwork_upload_description'])
            : 'Upload one or more artwork files for the production team.';
        $payload['artwork_upload_max_files'] = max(1, min(12, (int) ($data['artwork_upload_max_files'] ?? 5)));
        $payload['artwork_upload_max_file_size_mb'] = max(1, min(25, (int) ($data['artwork_upload_max_file_size_mb'] ?? 15)));
        $payload['artwork_upload_accepted_types'] = collect(explode(',', (string) ($data['artwork_upload_accepted_types'] ?? 'pdf,svg,png,jpg,jpeg,webp')))
            ->map(fn ($type) => Str::lower(ltrim(trim((string) $type), '.')))
            ->filter(fn ($type) => in_array($type, ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'], true))
            ->unique()->values()->implode(',');
        if ($payload['artwork_upload_accepted_types'] === '') {
            $payload['artwork_upload_accepted_types'] = 'pdf,svg,png,jpg,jpeg,webp';
        }
        $payload['price_table_headers'] = collect($data['price_table_headers'] ?? [])->map(fn ($value) => trim((string) $value))->filter()->values()->all();
        $headerCount = count($payload['price_table_headers']);
        $payload['price_table_rows'] = collect($data['price_table_rows'] ?? [])->map(function ($row) use ($headerCount) {
            $cells = collect($row)->take($headerCount ?: 20)->map(fn ($cell) => trim((string) $cell))->values()->all();
            return array_pad($cells, $headerCount, '');
        })->filter(fn ($row) => collect($row)->filter()->isNotEmpty())->values()->all();
        $payload['production_table_headers'] = collect($data['production_table_headers'] ?? [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->take(12)
            ->values()
            ->all();
        $productionColumnCount = count($payload['production_table_headers']);
        $payload['production_table_rows'] = collect($data['production_table_rows'] ?? [])
            ->map(function ($row) use ($productionColumnCount): array {
                $row = is_array($row) ? $row : [];
                $cells = collect($row['cells'] ?? [])->take($productionColumnCount)->map(function ($cell): array {
                    $productionTime = ProductionTime::parse($cell['production_time'] ?? null);
                    if ($productionTime === null && (array_key_exists('minimum_days', $cell) || array_key_exists('maximum_days', $cell))) {
                        $productionTime = ProductionTime::parse(ProductionTime::format(
                            $cell['minimum_days'] ?? 0,
                            $cell['maximum_days'] ?? ($cell['minimum_days'] ?? 0)
                        ));
                    }

                    return [
                        'enabled' => (bool) ($cell['enabled'] ?? false),
                        'description' => filled($cell['description'] ?? null) ? trim((string) $cell['description']) : null,
                        'price_adjustment' => max(0, round((float) ($cell['price_adjustment'] ?? 0), 2)),
                        'production_time' => $productionTime['display'] ?? '',
                        'minimum_days' => $productionTime['minimum_days'] ?? 0,
                        'maximum_days' => $productionTime['maximum_days'] ?? 0,
                    ];
                })->values()->all();

                return [
                    'range' => trim((string) ($row['range'] ?? '')),
                    'cells' => array_pad($cells, $productionColumnCount, [
                        'enabled' => false,
                        'description' => null,
                        'price_adjustment' => 0,
                        'production_time' => '',
                        'minimum_days' => 0,
                        'maximum_days' => 0,
                    ]),
                ];
            })
            ->filter(fn (array $row): bool => $row['range'] !== '' || collect($row['cells'])->contains(fn (array $cell): bool => $cell['enabled']))
            ->take(100)
            ->values()
            ->all();
        $payload['jersey_roster_fields'] = collect($data['jersey_roster_fields'] ?? [])
            ->filter(fn ($field) => (bool) ($field['enabled'] ?? false) && filled($field['key'] ?? null) && filled($field['label'] ?? null))
            ->map(fn ($field) => [
                'key' => Str::slug((string) $field['key'], '_'),
                'label' => trim((string) $field['label']),
                'type' => in_array(($field['type'] ?? 'text'), ['text', 'number'], true) ? $field['type'] : 'text',
                'required' => (bool) ($field['required'] ?? false),
                'enabled' => true,
                'max_length' => max(1, min(120, (int) ($field['max_length'] ?? 60))),
            ])->unique('key')->values()->all();
        if ($request->exists('schema_json_text')) {
            $payload['schema_json'] = filled($data['schema_json_text'] ?? null)
                ? json_decode($data['schema_json_text'], true, 512, JSON_THROW_ON_ERROR)
                : null;
        } elseif ($product === null) {
            $payload['schema_json'] = null;
        }

        $payload['created_by'] = $product?->created_by ?? auth()->id();
        $payload['updated_by'] = auth()->id();

        return $payload;
    }

    private function syncRelations(Product $product, ProductFormRequest $request): void
    {
        $data = $request->validated();
        $this->syncCatalogAssignments($product, $data);
        $this->syncImages($product, $request, $data);

        $existingOptionMedia = $product->optionGroups()
            ->with('values:id,product_option_group_id,jersey_customization_option_id,image_path,image_url,image_gallery')
            ->get()
            ->flatMap(fn ($group) => $group->values)
            ->mapWithKeys(fn ($value) => [(int) $value->id => [
                'master_id' => $value->jersey_customization_option_id,
                'path' => $value->image_path,
                'url' => $value->image_url,
                'gallery' => $value->image_gallery ?? [],
            ]])
            ->all();

        $submittedMasterOptionIds = collect($data['option_groups'] ?? [])
            ->flatMap(static fn ($group): array => collect($group['values'] ?? [])
                ->pluck('jersey_customization_option_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->all())
            ->unique()
            ->values();

        $masterOptions = JerseyCustomizationOption::query()
            ->active()
            ->with('images')
            ->whereIn('id', $submittedMasterOptionIds)
            ->get()
            ->keyBy('id');

        $product->optionGroups()->delete();
        $groupSortOrder = 0;

        foreach (collect($data['option_groups'] ?? [])->filter(fn ($group) => filled($group['name'] ?? null) && filled($group['code'] ?? null)) as $groupInputIndex => $groupData) {
            $group = $product->optionGroups()->create([
                'name' => trim((string) $groupData['name']),
                'code' => trim((string) $groupData['code']),
                'section' => $groupData['section'] ?? 'product',
                'type' => $groupData['type'] ?? 'select',
                'jersey_customization_type' => filled($groupData['jersey_customization_type'] ?? null) ? $groupData['jersey_customization_type'] : null,
                'display_mode' => $groupData['display_mode'] ?? 'customer',
                'fixed_value_code' => filled($groupData['fixed_value_code'] ?? null) ? trim((string) $groupData['fixed_value_code']) : null,
                'fixed_text_value' => filled($groupData['fixed_text_value'] ?? null) ? trim((string) $groupData['fixed_text_value']) : null,
                'show_in_summary' => (bool) ($groupData['show_in_summary'] ?? true),
                'use_as_filter' => (bool) ($groupData['use_as_filter'] ?? false),
                'catalog_attribute_id' => null,
                'description' => $groupData['description'] ?? null,
                'placeholder' => $groupData['placeholder'] ?? null,
                'is_required' => (bool) ($groupData['is_required'] ?? false),
                'minimum_selections' => $groupData['minimum_selections'] ?? null,
                'maximum_selections' => $groupData['maximum_selections'] ?? null,
                'accepted_file_types' => $groupData['accepted_file_types'] ?? null,
                'maximum_file_size_mb' => $groupData['maximum_file_size_mb'] ?? null,
                'is_active' => (bool) ($groupData['is_active'] ?? true),
                'sort_order' => $groupSortOrder++,
            ]);

            $valueSortOrder = 0;

            foreach (collect($groupData['values'] ?? [])->filter(fn ($value) => filled($value['label'] ?? null) && filled($value['code'] ?? null)) as $valueInputIndex => $valueData) {
                $masterOptionId = (int) ($valueData['jersey_customization_option_id'] ?? 0);
                $masterOption = $masterOptionId > 0 ? $masterOptions->get($masterOptionId) : null;
                $existingId = (int) ($valueData['existing_id'] ?? 0);
                $existing = $existingOptionMedia[$existingId] ?? ['master_id' => null, 'path' => null, 'url' => null, 'gallery' => []];

                if ($masterOption) {
                    $label = $masterOption->name;
                    $code = $masterOption->slug;
                    $description = $masterOption->description;
                    $colorHex = $masterOption->color_hex;

                    if ((int) ($existing['master_id'] ?? 0) === (int) $masterOption->id && ! empty($existing['gallery'])) {
                        $gallery = collect($existing['gallery'])->filter(fn ($item) => is_array($item))->values()->all();
                    } else {
                        $gallery = $masterOption->images
                            ->map(function ($image) use ($product, $masterOption): array {
                                $path = null;
                                if (filled($image->image_path) && Storage::disk('public')->exists($image->image_path)) {
                                    $extension = pathinfo($image->image_path, PATHINFO_EXTENSION);
                                    $path = "products/{$product->id}/options/"
                                        ."master-{$masterOption->id}-".Str::uuid()
                                        .($extension !== '' ? ".{$extension}" : '');
                                    Storage::disk('public')->copy($image->image_path, $path);
                                }

                                return [
                                    'path' => $path,
                                    'url' => $path ? null : $image->image_url,
                                    'alt' => $image->name ?: $masterOption->name,
                                ];
                            })
                            ->filter(fn (array $image): bool => filled($image['path']) || filled($image['url']))
                            ->values()
                            ->all();
                    }
                } else {
                    // Compatibility for products created before master-data assignment.
                    $label = trim((string) $valueData['label']);
                    $code = trim((string) $valueData['code']);
                    $description = $valueData['description'] ?? null;
                    $colorHex = $valueData['color_hex'] ?? null;
                    $gallery = (bool) ($valueData['clear_images'] ?? false)
                        ? []
                        : collect($existing['gallery'] ?? [])->filter(fn ($item) => is_array($item))->values()->all();

                    if ($gallery === []) {
                        if (filled($existing['path'] ?? null)) {
                            $gallery[] = ['path' => $existing['path'], 'url' => null, 'alt' => $label];
                        } elseif (filled($existing['url'] ?? null)) {
                            $gallery[] = ['path' => null, 'url' => $existing['url'], 'alt' => $label];
                        }
                    }

                    $imageUrl = filled($valueData['image_url'] ?? null) ? trim((string) $valueData['image_url']) : null;
                    if ($imageUrl && ! collect($gallery)->contains(fn ($item) => ($item['url'] ?? null) === $imageUrl)) {
                        $gallery[] = ['path' => null, 'url' => $imageUrl, 'alt' => $label];
                    }

                    $uploads = (array) $request->file("option_groups.{$groupInputIndex}.values.{$valueInputIndex}.image_files", []);
                    $legacyUpload = $request->file("option_groups.{$groupInputIndex}.values.{$valueInputIndex}.image_file");
                    if ($legacyUpload) {
                        $uploads[] = $legacyUpload;
                    }

                    foreach ($uploads as $uploadedImage) {
                        $gallery[] = [
                            'path' => $uploadedImage->store("products/{$product->id}/options", 'public'),
                            'url' => null,
                            'alt' => $label,
                        ];
                    }
                }

                $gallery = collect($gallery)->take(12)->values()->all();
                $firstImage = $gallery[0] ?? [];

                $group->values()->create([
                    'jersey_customization_option_id' => $masterOption?->id,
                    'label' => $label,
                    'code' => $code,
                    'description' => $description,
                    'color_hex' => $colorHex,
                    'image_path' => $firstImage['path'] ?? null,
                    'image_url' => $firstImage['url'] ?? null,
                    'image_gallery' => $gallery,
                    'price_adjustment' => $valueData['price_adjustment'] ?? 0,
                    'charge_type' => $valueData['charge_type'] ?? 'per_unit',
                    'stock_quantity' => $valueData['stock_quantity'] ?? null,
                    'is_default' => (bool) ($valueData['is_default'] ?? false),
                    'is_active' => (bool) ($valueData['is_active'] ?? true),
                    'sort_order' => $valueSortOrder++,
                ]);
            }
        }

        $this->productOptionFilterSyncService->sync(
            $product,
            collect($data['attribute_value_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        $submittedSizeGroups = collect($data['size_groups'] ?? [])->values();
        $sizeMasters = SizeOptionGroup::query()
            ->with(['sizes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->whereIn('id', $submittedSizeGroups->pluck('size_option_group_id')->filter()->map(fn ($id) => (int) $id)->unique())
            ->get()
            ->keyBy('id');

        $existingSizeMedia = $product->sizeGroups()
            ->get(['id', 'chart_image_path', 'chart_image_url'])
            ->mapWithKeys(fn ($group) => [(int) $group->id => [
                'path' => $group->chart_image_path,
                'url' => $group->chart_image_url,
            ]])->all();

        $product->sizeGroups()->delete();
        $sizeSortOrder = 0;
        foreach ($submittedSizeGroups as $groupInputIndex => $groupData) {
            $masterId = (int) ($groupData['size_option_group_id'] ?? 0);
            $master = $masterId > 0 ? $sizeMasters->get($masterId) : null;

            if ($master) {
                $group = $product->sizeGroups()->create([
                    'size_option_group_id' => $master->id,
                    'name' => $master->name,
                    'code' => $master->slug,
                    'description_html' => $master->description_html,
                    'chart_enabled' => filled($master->chart_html) || filled($master->chartImageUrl()),
                    'chart_html' => $master->chart_html,
                    'chart_title' => $master->chart_title,
                    'chart_note' => $master->chart_note,
                    'chart_columns' => $master->chart_columns ?? [],
                    'chart_rows' => $master->chart_rows ?? [],
                    'chart_image_path' => null,
                    'chart_image_url' => $master->chartImageUrl(),
                    'is_active' => true,
                    'sort_order' => $sizeSortOrder++,
                ]);

                foreach ($master->sizes as $sizeIndex => $size) {
                    $group->sizes()->create([
                        'label' => $size->label,
                        'code' => $size->code,
                        'sort_order' => $sizeIndex,
                        'is_active' => true,
                    ]);
                }

                continue;
            }

            // Backward compatibility for products that still contain a legacy
            // product-specific size group created before master data was added.
            if (! filled($groupData['name'] ?? null) || ! filled($groupData['code'] ?? null)) {
                continue;
            }

            $existingId = (int) ($groupData['existing_id'] ?? 0);
            $existingMedia = $existingSizeMedia[$existingId] ?? ['path' => null, 'url' => null];
            $chartImageUrl = filled($groupData['chart_image_url'] ?? null) ? trim((string) $groupData['chart_image_url']) : null;
            $chartImagePath = $chartImageUrl ? null : ($existingMedia['path'] ?? null);
            if ((bool) ($groupData['clear_chart_image'] ?? false)) {
                $chartImagePath = null;
                $chartImageUrl = null;
            }
            if ($chartImage = $request->file("size_groups.{$groupInputIndex}.chart_image")) {
                $chartImagePath = $chartImage->store("products/{$product->id}/size-charts", 'public');
                $chartImageUrl = null;
            } elseif (! $chartImageUrl && ! $chartImagePath && filled($existingMedia['url'] ?? null)) {
                $chartImageUrl = $existingMedia['url'];
            }

            $columns = collect(preg_split('/[,\r\n]+/', (string) ($groupData['chart_columns_text'] ?? '')))
                ->map(fn ($column) => trim((string) $column))->filter()->take(12)->values()->all();
            $rows = $this->parseChartRows((string) ($groupData['chart_rows_text'] ?? ''), count($columns));

            $group = $product->sizeGroups()->create([
                'size_option_group_id' => null,
                'name' => trim((string) $groupData['name']),
                'code' => trim((string) $groupData['code']),
                'description_html' => $this->safeHtml->sanitize($groupData['description_html'] ?? null),
                'chart_html' => $this->safeHtml->sanitize($groupData['chart_html'] ?? null),
                'chart_enabled' => (bool) ($groupData['chart_enabled'] ?? false),
                'chart_title' => $groupData['chart_title'] ?? null,
                'chart_note' => $groupData['chart_note'] ?? null,
                'chart_columns' => $columns,
                'chart_rows' => $rows,
                'chart_image_path' => $chartImagePath,
                'chart_image_url' => $chartImageUrl,
                'is_active' => (bool) ($groupData['is_active'] ?? true),
                'sort_order' => $sizeSortOrder++,
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
        // Artwork is now a single configurable multi-file upload section. Remove
        // legacy method cards so stale choices can never reappear on the storefront.
        $product->artworkMethods()->delete();
        $this->replaceSimpleRelation($product, 'productionSpeeds', $data['production_speeds'] ?? [], ['name', 'code'], [
            'name', 'code', 'description', 'price_adjustment', 'minimum_quantity', 'maximum_quantity',
            'minimum_days', 'maximum_days', 'is_active',
        ]);
        $this->replaceSimpleRelation($product, 'shippingMethods', $data['shipping_methods'] ?? [], ['name', 'code'], [
            'name', 'code', 'description', 'price_adjustment', 'charge_type', 'minimum_days', 'maximum_days', 'is_default', 'is_active',
        ]);
        $this->replaceSimpleRelation($product, 'faqs', $data['faqs'] ?? [], ['question', 'answer'], ['question', 'answer', 'is_active']);
    }

    private function parseChartRows(string $value, int $columnCount): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(function (string $line) use ($columnCount): array {
                $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, '|') ? '|' : ',');
                $cells = collect(explode($delimiter, $line))->map(fn ($cell) => trim((string) $cell))->take($columnCount ?: 12)->values()->all();

                return $columnCount > 0 ? array_pad($cells, $columnCount, '') : $cells;
            })
            ->filter(fn ($row) => collect($row)->filter(fn ($cell) => $cell !== '')->isNotEmpty())
            ->take(100)
            ->values()
            ->all();
    }

    private function syncCatalogAssignments(Product $product, array $data): void
    {
        $showInCategoryPage = (bool) ($data['show_in_category_page'] ?? true);
        $primaryId = isset($data['primary_category_id']) ? (int) $data['primary_category_id'] : null;
        $categoryIds = collect($data['category_assignments'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($showInCategoryPage && $primaryId) {
            $categoryIds = $categoryIds->prepend($primaryId)->unique()->values();
        } elseif ($showInCategoryPage && $categoryIds->isNotEmpty()) {
            $primaryId = (int) $categoryIds->first();
        } elseif (! $showInCategoryPage) {
            $categoryIds = collect();
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
        $existingImages = $product->images()->get()->keyBy('id');
        $keptIds = [];
        $orderedImageIds = [];

        $urlRows = collect($data['image_urls'] ?? [])->values();
        $uploadedImages = collect($request->file('images', []))->values();
        $requestedUploadPrimary = filled($data['new_image_primary_index'] ?? null)
            ? (int) $data['new_image_primary_index']
            : null;
        $uploadPrimaryIsValid = $requestedUploadPrimary !== null
            && $uploadedImages->has($requestedUploadPrimary);
        $requestedUrlPrimary = $urlRows->search(fn ($item) => (bool) ($item['is_primary'] ?? false));
        $primaryImageId = null;

        foreach ($urlRows as $index => $item) {
            $existingId = (int) ($item['existing_id'] ?? 0);
            $url = trim((string) ($item['url'] ?? ''));
            $name = filled($item['name'] ?? null)
                ? trim((string) $item['name'])
                : (filled($item['alt'] ?? null) ? trim((string) $item['alt']) : $product->name);

            $image = $existingId > 0 ? $existingImages->get($existingId) : null;

            if ($existingId > 0 && ! $image) {
                throw ValidationException::withMessages([
                    "image_urls.{$index}.existing_id" => 'The selected product image does not belong to this product.',
                ]);
            }

            if ($image) {
                // A saved upload is preserved when the URL field stays blank.
                // Entering a URL intentionally replaces the saved upload.
                if ($url !== '') {
                    $this->deletePublicImage($image->path);
                    $image->path = null;
                    $image->url = $url;
                } elseif (! filled($image->path)) {
                    $legacyStoredPath = PublicMedia::storedPathFromUrl($image->url);
                    if ($legacyStoredPath && Storage::disk('public')->exists($legacyStoredPath)) {
                        // Earlier versions converted uploaded images into absolute
                        // /storage URLs during an edit. Repair that record in place.
                        $image->path = $legacyStoredPath;
                        $image->url = null;
                    } else {
                        // A genuine remote image whose URL was cleared is removed.
                        continue;
                    }
                }

                $image->alt_text = $name;
                $image->is_primary = false;
                $image->sort_order = count($orderedImageIds);
                $image->save();
            } else {
                if ($url === '') {
                    continue;
                }

                $image = $product->images()->create([
                    'url' => $url,
                    'alt_text' => $name,
                    'is_primary' => false,
                    'sort_order' => count($orderedImageIds),
                ]);
            }

            $keptIds[] = $image->id;
            $orderedImageIds[] = $image->id;

            if (! $uploadPrimaryIsValid && $requestedUrlPrimary !== false && (int) $requestedUrlPrimary === $index) {
                $primaryImageId = $image->id;
            }
        }

        foreach ($uploadedImages as $index => $uploadedImage) {
            $path = $uploadedImage->store("products/{$product->id}", 'public');
            $image = $product->images()->create([
                'path' => $path,
                'url' => null,
                'alt_text' => pathinfo($uploadedImage->getClientOriginalName(), PATHINFO_FILENAME) ?: $product->name,
                'is_primary' => false,
                'sort_order' => count($orderedImageIds),
            ]);

            $keptIds[] = $image->id;
            $orderedImageIds[] = $image->id;

            if ($uploadPrimaryIsValid && $requestedUploadPrimary === $index) {
                $primaryImageId = $image->id;
            }
        }

        $existingImages
            ->reject(fn ($image) => in_array($image->id, $keptIds, true))
            ->each(function ($image): void {
                $this->deletePublicImage($image->path);
                $image->delete();
            });

        if ($primaryImageId === null) {
            $primaryImageId = $orderedImageIds[0] ?? null;
        }

        $product->images()->update(['is_primary' => false]);
        if ($primaryImageId !== null) {
            $product->images()->whereKey($primaryImageId)->update(['is_primary' => true]);
        }
    }

    private function deletePublicImage(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
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
            foreach (['requires_upload', 'is_active', 'is_default'] as $boolean) {
                if (array_key_exists($boolean, $payload)) {
                    $payload[$boolean] = (bool) $payload[$boolean];
                }
            }
            $payload['sort_order'] = $index;
            $product->{$relation}()->create($payload);
        }
    }
}
