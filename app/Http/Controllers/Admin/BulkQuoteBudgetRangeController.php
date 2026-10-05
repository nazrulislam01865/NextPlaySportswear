<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkQuoteBudgetRangeRequest;
use App\Models\BulkQuoteBudgetRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BulkQuoteBudgetRangeController extends Controller
{
    public function index(): View
    {
        return view('admin.bulk-quote-budget-ranges.index', [
            'budgetRanges' => BulkQuoteBudgetRange::query()
                ->ordered()
                ->paginate($this->adminPerPage(30))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.bulk-quote-budget-ranges.create', [
            'budgetRange' => new BulkQuoteBudgetRange([
                'is_active' => true,
                'sort_order' => ((int) BulkQuoteBudgetRange::query()->max('sort_order')) + 10,
            ]),
        ]);
    }

    public function store(BulkQuoteBudgetRangeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?: (((int) BulkQuoteBudgetRange::query()->max('sort_order')) + 10));

        BulkQuoteBudgetRange::query()->create($data);

        return redirect()->route('admin.bulk-quote-budget-ranges.index')
            ->with('status', 'Bulk quote budget range created successfully.');
    }

    public function edit(BulkQuoteBudgetRange $bulkQuoteBudgetRange): View
    {
        return view('admin.bulk-quote-budget-ranges.edit', [
            'budgetRange' => $bulkQuoteBudgetRange,
        ]);
    }

    public function update(BulkQuoteBudgetRangeRequest $request, BulkQuoteBudgetRange $bulkQuoteBudgetRange): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? $bulkQuoteBudgetRange->sort_order ?? 0);
        $bulkQuoteBudgetRange->update($data);

        return redirect()->route('admin.bulk-quote-budget-ranges.index')
            ->with('status', 'Bulk quote budget range updated successfully.');
    }

    public function destroy(BulkQuoteBudgetRange $bulkQuoteBudgetRange): RedirectResponse
    {
        $bulkQuoteBudgetRange->delete();

        return redirect()->route('admin.bulk-quote-budget-ranges.index')
            ->with('status', 'Budget range removed from Master Data. Existing quotes keep their saved value.');
    }
}
