<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeFormRequest;
use App\Models\CatalogAttribute;
use App\Models\CatalogAttributeValue;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function __construct(private readonly CategoryTreeService $treeService)
    {
    }
    public function index(Request $request): View
    {
        $query = CatalogAttribute::query()->withCount(['values', 'categories']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        }
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return view('admin.attributes.index', [
            'attributes' => $query->ordered()->paginate(30)->withQueryString(),
            'filters' => $request->only(['q', 'active']),
        ]);
    }

    public function create(): View
    {
        return view('admin.attributes.create', ['attribute' => new CatalogAttribute([
            'display_type' => 'checkbox', 'is_filterable' => true, 'is_searchable' => false,
            'is_active' => true, 'sort_order' => 0,
        ])]);
    }

    public function store(AttributeFormRequest $request): RedirectResponse
    {
        $attribute = DB::transaction(function () use ($request): CatalogAttribute {
            $attribute = CatalogAttribute::query()->create($this->payload($request));
            $this->syncValues($attribute, $request);
            return $attribute;
        });

        $this->treeService->flushCache();

        return redirect()->route('admin.attributes.edit', $attribute)->with('status', 'Catalog attribute created successfully.');
    }

    public function edit(CatalogAttribute $attribute): View
    {
        $attribute->load('values');
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(AttributeFormRequest $request, CatalogAttribute $attribute): RedirectResponse
    {
        DB::transaction(function () use ($request, $attribute): void {
            $attribute->update($this->payload($request));
            $this->syncValues($attribute, $request);
        });

        $this->treeService->flushCache();

        return redirect()->route('admin.attributes.edit', $attribute)->with('status', 'Catalog attribute updated successfully.');
    }

    public function destroy(CatalogAttribute $attribute): RedirectResponse
    {
        if ($attribute->categories()->exists() || $attribute->values()->whereHas('products')->exists()) {
            return back()->withErrors(['attribute' => 'This attribute is used by categories or products. Remove those assignments before deleting it.']);
        }

        foreach ($attribute->values as $value) {
            $this->deleteImage($value->image_path);
        }
        $attribute->delete();
        $this->treeService->flushCache();

        return redirect()->route('admin.attributes.index')->with('status', 'Catalog attribute moved to trash.');
    }

    private function payload(AttributeFormRequest $request): array
    {
        return array_merge(Arr::only($request->validated(), [
            'name', 'slug', 'display_type', 'unit', 'is_filterable', 'is_searchable', 'is_active', 'sort_order',
        ]), [
            'created_by' => $request->route('attribute')?->created_by ?? auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    private function syncValues(CatalogAttribute $attribute, AttributeFormRequest $request): void
    {
        $existing = $attribute->values()->get()->keyBy('id');
        $kept = [];

        foreach ($request->validated('values', []) as $index => $input) {
            if (! filled($input['label'] ?? null)) {
                continue;
            }

            $value = $existing->get((int) ($input['existing_id'] ?? 0)) ?? new CatalogAttributeValue(['attribute_id' => $attribute->id]);
            abort_if($value->exists && (int) $value->attribute_id !== (int) $attribute->id, 422);

            $imageUrl = filled($input['image_url'] ?? null) ? trim((string) $input['image_url']) : null;
            $imagePath = $imageUrl ? null : $value->image_path;
            $uploaded = $request->file("values.{$index}.image_file");

            if ($uploaded) {
                $this->deleteImage($value->image_path);
                $imagePath = $uploaded->store("catalog/attributes/{$attribute->id}", 'public');
                $imageUrl = null;
            } elseif ($imageUrl && $value->image_path) {
                $this->deleteImage($value->image_path);
            }

            $value->fill([
                'attribute_id' => $attribute->id,
                'label' => trim((string) $input['label']),
                'slug' => $input['slug'],
                'color_hex' => $input['color_hex'] ?? null,
                'image_path' => $imagePath,
                'image_url' => $imageUrl,
                'numeric_value' => $input['numeric_value'] ?? null,
                'is_active' => (bool) ($input['is_active'] ?? true),
                'sort_order' => (int) ($input['sort_order'] ?? $index),
            ])->save();
            $kept[] = $value->id;
        }

        $attribute->values()->whereNotIn('id', $kept ?: [0])->get()->each(function (CatalogAttributeValue $value): void {
            if ($value->products()->exists()) {
                $value->update(['is_active' => false]);
                return;
            }
            $this->deleteImage($value->image_path);
            $value->delete();
        });
    }

    private function deleteImage(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
