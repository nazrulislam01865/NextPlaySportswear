<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\GuestBulkQuoteController;
use App\Models\BulkQuoteFile;
use App\Models\BulkQuoteRequest;
use App\Services\BulkQuote\BulkQuoteGuestService;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkQuoteGuestAdminController extends Controller
{
    public function __construct(
        private readonly BulkQuoteGuestService $files,
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
            'files' => ['required', 'array', 'min:1', 'max:8'],
            'files.*' => ['required', 'file', 'mimes:pdf,xlsx,xls,png,jpg,jpeg', 'max:10240'],
        ]);
        $total = round((float) $data['subtotal'] + (float) $data['customization'] + (float) $data['shipping'], 2);
        if ($total <= 0 || $total > 9999999999.99) {
            throw ValidationException::withMessages(['subtotal' => 'The final quotation total must be greater than zero and within the supported amount.']);
        }
        $uploaded = [];
        try {
            DB::transaction(function () use ($bulkQuote, $request, $data, $total, &$uploaded): void {
                $quote = BulkQuoteRequest::query()->whereKey($bulkQuote->id)->lockForUpdate()->firstOrFail();
                if (in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED, BulkQuoteRequest::STATUS_CLOSED], true)) {
                    throw ValidationException::withMessages(['quote' => 'The quotation is already finalized. A new version cannot be sent.']);
                }
                $version = $quote->quotation_version + 1;
                foreach ($request->file('files', []) as $file) {
                    $uploaded[] = $this->files->storeFile($quote, $file, 'quotation', $version, $request->user('admin')->id);
                }
                $quote->forceFill([
                    'quotation_version' => $version,
                    'quotation_details' => [
                        'quantity' => (int) $data['quantity'],
                        'subtotal' => round((float) $data['subtotal'], 2),
                        'customization' => round((float) $data['customization'], 2),
                        'shipping' => round((float) $data['shipping'], 2),
                        'shipping_method' => $data['shipping_method'] ?? '',
                        'payment_terms' => $data['payment_terms'],
                        'quotation_notes' => $data['quotation_notes'] ?? '',
                    ],
                    'quoted_amount' => $total,
                    'quote_currency' => 'USD',
                    'quotation_valid_until' => $data['valid_until'],
                    'quotation_sent_at' => now(),
                    'guest_decision' => null,
                    'status' => BulkQuoteRequest::STATUS_QUOTATION_SENT,
                    'last_contacted_at' => now(),
                ])->save();
                $quote->activities()->create([
                    'actor_id' => $request->user('admin')->id,
                    'action' => 'quotation_published',
                    'from_status' => $bulkQuote->status,
                    'to_status' => BulkQuoteRequest::STATUS_QUOTATION_SENT,
                    'note' => 'Quotation version '.$version.' published with '.count($uploaded).' file(s).',
                    'occurred_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            $this->files->deleteFiles($uploaded);
            throw $exception;
        }
        $this->emails->bulkQuoteUpdate($bulkQuote->fresh(), 'Your quotation is ready', 'Your custom quotation files are available for review.');
        return back()->with('status', 'Quotation version published. A customer email update was queued if delivery is enabled.');
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
