<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use App\Services\Storefront\ProductCatalogCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(
        private readonly ProductCatalogCacheService $productCatalogCache,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Faq::query()->withCount('products');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'active');
        }

        return view('admin.faqs.index', [
            'faqs' => $query->ordered()->paginate(20)->withQueryString(),
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('admin.faqs.create', [
            'faq' => new Faq([
                'is_active' => true,
                'sort_order' => ((int) Faq::query()->max('sort_order')) + 10,
            ]),
        ]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::query()->create($request->validated());
        $this->productCatalogCache->flush();

        return redirect()->route('admin.faqs.index')
            ->with('status', 'FAQ created successfully. It is now available on product add/edit pages.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());
        $this->productCatalogCache->flush();

        return redirect()->route('admin.faqs.index')
            ->with('status', 'FAQ updated successfully. Assigned products use the updated content automatically.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $affectedProducts = $faq->products()->count();
        $faq->delete();
        $this->productCatalogCache->flush();

        return redirect()->route('admin.faqs.index')
            ->with('status', 'FAQ deleted'.($affectedProducts > 0 ? " and removed from {$affectedProducts} product(s)." : '.'));
    }
}
