<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkQuotePaymentTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class BulkQuotePaymentTermController extends Controller
{
    public function index(): View
    {
        abort_unless(auth('admin')->user()?->canAdmin('orders.view'), 403);
        return view('admin.bulk-quote-payment-terms.index', [
            'terms' => BulkQuotePaymentTerm::query()->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:200', 'unique:bulk_quote_payment_terms,label'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['required', 'boolean'],
        ]);
        BulkQuotePaymentTerm::query()->create($data);
        return back()->with('status', 'Quotation payment term added.');
    }

    public function update(Request $request, BulkQuotePaymentTerm $bulkQuotePaymentTerm): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:200', Rule::unique('bulk_quote_payment_terms', 'label')->ignore($bulkQuotePaymentTerm->id)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['required', 'boolean'],
        ]);
        $bulkQuotePaymentTerm->update($data);
        return back()->with('status', 'Quotation payment term updated.');
    }

    public function destroy(Request $request, BulkQuotePaymentTerm $bulkQuotePaymentTerm): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        $bulkQuotePaymentTerm->delete();
        return back()->with('status', 'Quotation payment term removed. Published quotations retain their saved wording.');
    }
}
