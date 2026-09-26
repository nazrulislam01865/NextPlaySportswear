<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenderRequest;
use App\Models\Gender;
use App\Services\Storefront\ProductCatalogCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenderController extends Controller
{
    public function __construct(private readonly ProductCatalogCacheService $productCatalogCache)
    {
    }

    public function index(): View
    {
        return view('admin.genders.index', [
            'genders' => Gender::query()
                ->withCount('products')
                ->ordered()
                ->paginate($this->adminPerPage(20))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.genders.create', [
            'gender' => new Gender([
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function store(GenderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['sort_order'])) {
            $data['sort_order'] = ((int) Gender::query()->max('sort_order')) + 10;
        }

        Gender::query()->create($data);
        $this->productCatalogCache->flush();

        return redirect()->route('admin.genders.index')
            ->with('status', 'Gender created successfully.');
    }

    public function edit(Gender $gender): View
    {
        return view('admin.genders.edit', compact('gender'));
    }

    public function update(GenderRequest $request, Gender $gender): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = $gender->sort_order ?: (((int) Gender::query()->whereKeyNot($gender->id)->max('sort_order')) + 10);
        $gender->update($data);
        $this->productCatalogCache->flush();

        return redirect()->route('admin.genders.index')
            ->with('status', 'Gender updated successfully.');
    }

    public function destroy(Gender $gender): RedirectResponse
    {
        $gender->delete();
        $this->productCatalogCache->flush();

        return redirect()->route('admin.genders.index')
            ->with('status', 'Gender removed. Products that used it are now left without a gender selection.');
    }
}
