<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductionMethodRequest;
use App\Models\ProductionMethod;
use App\Services\Catalog\MasterMethodMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductionMethodController extends Controller
{
    public function __construct(private readonly MasterMethodMediaService $media)
    {
    }

    public function index(): View
    {
        return view('admin.production-methods.index', [
            'methods' => ProductionMethod::query()->orderBy('sort_order')->latest()->paginate($this->adminPerPage(20))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.production-methods.create', [
            'method' => new ProductionMethod([
                'minimum_days' => 7,
                'maximum_days' => 10,
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function store(ProductionMethodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['sort_order'])) {
            $data['sort_order'] = ((int) ProductionMethod::query()->max('sort_order')) + 10;
        }

        unset($data['image_file'], $data['image_url'], $data['remove_image']);
        $method = ProductionMethod::create($data);
        $this->media->persist($method, $request, 'master-data/production-methods');
        $this->syncDefault($method);

        return redirect()->route('admin.production-methods.index')
            ->with('status', 'Production method created successfully.');
    }

    public function edit(ProductionMethod $productionMethod): View
    {
        return view('admin.production-methods.edit', [
            'method' => $productionMethod,
        ]);
    }

    public function update(ProductionMethodRequest $request, ProductionMethod $productionMethod): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = $productionMethod->sort_order ?: (((int) ProductionMethod::query()->whereKeyNot($productionMethod->id)->max('sort_order')) + 10);
        unset($data['image_file'], $data['image_url'], $data['remove_image']);
        $productionMethod->update($data);
        $this->media->persist($productionMethod, $request, 'master-data/production-methods');
        $this->syncDefault($productionMethod);

        return redirect()->route('admin.production-methods.index')
            ->with('status', 'Production method updated successfully.');
    }

    public function destroy(ProductionMethod $productionMethod): RedirectResponse
    {
        $this->media->deleteOwned($productionMethod->image_path, 'master-data/production-methods');
        $productionMethod->delete();

        if (! ProductionMethod::query()->where('is_default', true)->exists()) {
            ProductionMethod::query()->where('is_active', true)->orderBy('sort_order')->first()?->update(['is_default' => true]);
        }

        return redirect()->route('admin.production-methods.index')
            ->with('status', 'Production method removed.');
    }

    private function syncDefault(ProductionMethod $method): void
    {
        if (! $method->is_default) {
            return;
        }

        ProductionMethod::query()->whereKeyNot($method->id)->update(['is_default' => false]);
    }
}
