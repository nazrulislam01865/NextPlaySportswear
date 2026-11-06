<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\GuestBulkQuoteController;
use App\Models\BulkQuoteFile;
use App\Models\BulkQuoteRequest;
use App\Models\BulkQuotePaymentTerm;
use App\Services\BulkQuote\BulkQuoteQuotationEditor;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkQuoteGuestAdminController extends Controller
{
    public function __construct(
        private readonly TransactionalEmailManager $emails,
    ) {}

    public function publish(Request $request, BulkQuoteRequest $bulkQuote, BulkQuoteQuotationEditor $editor): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        $this->applyMasterPaymentTerm($request);
        $publication = $request->validate([
            'payment_terms' => ['required', 'string', 'max:1000'],
            'quotation_notes' => ['nullable', 'string', 'max:2500'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'use_flowtrack_offer' => ['nullable', 'boolean'],
            'files' => ['sometimes', 'array', 'max:8'],
            'files.*' => ['required', 'file', 'mimes:pdf,xlsx,xls,png,jpg,jpeg', 'max:10240'],
        ]);
        if ($request->exists('lines')) {
            // Resolve every option against the catalogue and recalculate totals on the server.
            // Never accept an editable browser grand total as an authoritative amount.
            $data = array_merge($publication, $editor->calculate($bulkQuote, $request->all()));
        } else {
            // Preserve the existing publication contract for FlowTracker and older clients.
            $legacy = $request->validate([
                'quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
                'subtotal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
                'customization' => ['required', 'numeric', 'min:0', 'max:9999999999'],
                'shipping' => ['required', 'numeric', 'min:0', 'max:9999999999'],
                'shipping_method' => ['nullable', 'string', 'max:160'],
            ]);
            $data = array_merge($publication, $legacy);
        }
        app(\App\Services\BulkQuote\UnifiedQuotePublisher::class)->fromAdmin(
            (int) $bulkQuote->id, $data, $request->file('files', []), (int) $request->user('admin')->id
        );
        return redirect()->route('admin.bulk-quotes.show', ['bulkQuote' => $bulkQuote, 'tab' => 'messages'])
            ->with('status', 'Quotation published and synchronized with FlowTracker.');
    }

    public function draft(Request $request, BulkQuoteRequest $bulkQuote, BulkQuoteQuotationEditor $editor): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        if (in_array($bulkQuote->status, [
            BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED,
            BulkQuoteRequest::STATUS_CLOSED, BulkQuoteRequest::STATUS_REJECTED,
        ], true)) {
            return back()->withErrors(['quote' => 'This quotation has been finalized.']);
        }
        $this->applyMasterPaymentTerm($request);
        $request->validate([
            'payment_terms' => ['nullable', 'string', 'max:1000'],
            'quotation_notes' => ['nullable', 'string', 'max:2500'],
            'valid_until' => ['nullable', 'date'],
        ]);
        $calculated = $editor->calculate($bulkQuote, $request->all(), true);
        $bulkQuote->forceFill(['quotation_draft' => [
            'lines' => $calculated['pricing_lines'],
            'sample_enabled' => (bool) ($calculated['sample']['enabled'] ?? false),
            'shipping_method' => strtolower($calculated['shipping_method']),
            'sample_shipping_method' => $calculated['sample']['shipping_method'],
            'remote_charge' => $request->input('remote_charge', 0),
            'sample_remote_charge' => $request->input('sample_remote_charge', 0),
            'private_address_charge' => $request->input('private_address_charge', 0),
            'sample_private_charge' => $request->input('sample_private_charge', 0),
            'valid_until' => $request->input('valid_until'),
            'payment_terms' => $request->input('payment_terms'),
            'quotation_notes' => $request->input('quotation_notes'),
            'use_flowtrack_offer' => $request->boolean('use_flowtrack_offer'),
            'updated_at' => now()->toIso8601String(),
        ]])->saveQuietly();

        return redirect()->route('admin.bulk-quotes.show', ['bulkQuote' => $bulkQuote, 'tab' => 'messages'])
            ->with('status', 'Quotation draft saved privately. Nothing has been sent to the customer.');
    }

    private function applyMasterPaymentTerm(Request $request): void
    {
        // Legacy clients without a term ID can continue posting the existing text contract.
        // The redesigned form always sends an active Master Data identifier.
        if (! $request->exists('payment_term_id')) return;
        $validated = $request->validate([
            'payment_term_id' => ['required', 'integer', Rule::exists('bulk_quote_payment_terms', 'id')->where('is_active', true)],
        ]);
        $term = BulkQuotePaymentTerm::query()->active()->findOrFail($validated['payment_term_id']);
        $request->merge(['payment_terms' => $term->label]);
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
