<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\GuestBulkQuoteController;
use App\Models\BulkQuoteFile;
use App\Models\BulkQuoteRequest;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkQuoteGuestAdminController extends Controller
{
    public function __construct(
        private readonly TransactionalEmailManager $emails,
    ) {}

    public function publish(Request $request, BulkQuoteRequest $bulkQuote): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'customization' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'shipping' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'shipping_method' => ['nullable', 'string', 'max:160'],
            'payment_terms' => ['required', 'string', 'max:1000'],
            'quotation_notes' => ['nullable', 'string', 'max:2500'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'use_flowtrack_offer' => ['nullable', 'boolean'],
            'files' => ['sometimes', 'array', 'max:8'],
            'files.*' => ['required', 'file', 'mimes:pdf,xlsx,xls,png,jpg,jpeg', 'max:10240'],
        ]);
        app(\App\Services\BulkQuote\UnifiedQuotePublisher::class)->fromAdmin(
            (int) $bulkQuote->id, $data, $request->file('files', []), (int) $request->user('admin')->id
        );
        return back()->with('status', 'Quotation published and synchronized with FlowTracker.');
    }

    public function message(Request $request, BulkQuoteRequest $bulkQuote): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:3000']]);
        $bulkQuote->messages()->create([
            'actor_id' => $request->user('admin')->id,
            'sender' => 'admin',
            'kind' => 'message',
            'body' => trim($data['body']),
        ]);
        $this->emails->bulkQuoteUpdate($bulkQuote, 'Message from NextPlay Sales', trim($data['body']));
        return back()->with('status', 'Message saved in the customer quotation timeline.');
    }

    public function file(Request $request, BulkQuoteRequest $bulkQuote, BulkQuoteFile $file): StreamedResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.view'), 403);
        abort_unless($file->bulk_quote_request_id === $bulkQuote->id, 404);
        return GuestBulkQuoteController::download($file, $request->boolean('view'));
    }
}
