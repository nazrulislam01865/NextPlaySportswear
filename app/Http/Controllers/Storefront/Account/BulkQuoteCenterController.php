<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\GuestBulkQuoteController;
use App\Models\BulkQuoteFile;
use App\Models\BulkQuoteRequest;
use App\Services\BulkQuote\BulkQuoteGuestService;
use App\Services\Email\TransactionalEmailManager;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkQuoteCenterController extends Controller
{
    public function __construct(
        private readonly CustomerAccountService $accounts,
        private readonly BulkQuoteGuestService $quotes,
        private readonly TransactionalEmailManager $emails,
    ) {}

    private function mine(Request $request, string $reference): BulkQuoteRequest
    {
        // A quote reference or someone's email alone never establishes ownership.
        return BulkQuoteRequest::query()->where('user_id', $request->user()->id)
            ->where('reference', $reference)->firstOrFail();
    }

    private function page(string $view, Request $request, array $data = []): View
    {
        return view('storefront.account.bulk-quotes.'.$view, array_merge([
            'account' => $this->accounts->sidebarData($request->user()),
            'navigation' => $this->accounts->accountNavigation(),
            'seo' => ['title' => 'My Bulk Quotes | NextPlay Sportswear', 'robots' => 'noindex, nofollow'],
        ], $data));
    }

    private function files(BulkQuoteRequest $quote)
    {
        return $quote->quoteFiles()->where('category', 'quotation')
            ->where('version', $quote->quotation_version)->oldest()->get();
    }

    public function index(Request $request): View
    {
        $base = BulkQuoteRequest::query()->where('user_id', $request->user()->id);
        // Aggregate status counts in one query; only paginate actual visible quotes.
        $groups = (clone $base)->select('status')->selectRaw('COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status');
        $counts = [
            'all' => (int) $groups->sum(),
            'pending' => (int) collect(['new', 'under_review', 'qualified', 'quotation_preparing'])->sum(fn ($s) => $groups[$s] ?? 0),
            'sent' => (int) ($groups[BulkQuoteRequest::STATUS_QUOTATION_SENT] ?? 0),
            'discussion' => (int) ($groups[BulkQuoteRequest::STATUS_CUSTOMER_RESPONDED] ?? 0),
            'confirmed' => (int) collect(['accepted', 'converted'])->sum(fn ($s) => $groups[$s] ?? 0),
            'closed' => (int) collect(['closed', 'rejected'])->sum(fn ($s) => $groups[$s] ?? 0),
        ];
        $filter = (string) $request->query('status', 'all');
        if (! array_key_exists($filter, $counts)) {
            $filter = 'all';
        }
        $byTab = [
            'pending' => ['new', 'under_review', 'qualified', 'quotation_preparing'],
            'sent' => ['quotation_sent'],
            'discussion' => ['customer_responded'],
            'confirmed' => ['accepted', 'converted'],
            'closed' => ['closed', 'rejected'],
        ];
        $query = clone $base;
        if (isset($byTab[$filter])) {
            $query->whereIn('status', $byTab[$filter]);
        }
        $quotes = $query->withCount(['quoteFiles as current_files_count' => fn ($q) => $q->where('category', 'quotation')
            ->whereColumn('version', 'bulk_quote_requests.quotation_version')])
            ->latest('id')->paginate(12)->withQueryString();
        return $this->page('index', $request, compact('quotes', 'counts', 'filter'));
    }

    public function show(Request $request, string $reference): View
    {
        $quote = $this->mine($request, $reference);
        $quote->load('convertedOrder:id,order_number,user_id');
        return $this->page('show', $request, [
            'quote' => $quote,
            'files' => $this->files($quote),
            'messages' => $quote->messages()->with('files')->oldest()->limit(100)->get(),
        ]);
    }

    public function changes(Request $request, string $reference): View|RedirectResponse
    {
        $quote = $this->mine($request, $reference);
        if (! $this->canRespond($quote)) {
            return redirect()->route('account.bulk-quotes.show', $reference)
                ->withErrors(['quote' => 'This quotation is not available for changes. Contact sales if you need assistance.']);
        }
        return $this->page('changes', $request, [
            'quote' => $quote,
            'files' => $this->files($quote),
            'messages' => $quote->messages()->with('files')->latest()->limit(10)->get(),
        ]);
    }

    private function canRespond(BulkQuoteRequest $quote): bool
    {
        return $quote->quotation_version > 0 && $quote->quotation_sent_at !== null &&
            ! in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED,
                BulkQuoteRequest::STATUS_CLOSED, BulkQuoteRequest::STATUS_REJECTED], true);
    }

    public function sendChanges(Request $request, string $reference): RedirectResponse
    {
        $quote = $this->mine($request, $reference);
        abort_unless($this->canRespond($quote), 404);
        $data = $request->validate([
            'change_types' => ['required', 'array', 'min:1', 'max:6'],
            'change_types.*' => [Rule::in(['pricing', 'delivery', 'products', 'customization', 'payment', 'other'])],
            'body' => ['required', 'string', 'min:5', 'max:1000'],
            'files' => ['nullable', 'array', 'max:4'],
            'files.*' => ['file', 'mimes:pdf,xlsx,xls,png,jpg,jpeg', 'max:10240'],
        ]);
        $this->quotes->storeCustomerMessage($quote, trim($data['body']), 'change_request', $data['change_types'], $request->file('files', []), $request->user()->id);
        $this->notify($quote, 'Change request', trim($data['body']));
        return redirect()->route('account.bulk-quotes.show', $reference)
            ->with('status', 'Your change request has been sent to NextPlay. Our team will review it.');
    }

    public function sendMessage(Request $request, string $reference): RedirectResponse
    {
        $quote = $this->mine($request, $reference);
        abort_unless($this->canRespond($quote), 404);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
            'files' => ['nullable', 'array', 'max:4'],
            'files.*' => ['file', 'mimes:pdf,xlsx,xls,png,jpg,jpeg', 'max:10240'],
        ]);
        $this->quotes->storeCustomerMessage($quote, trim($data['body']), 'message', [], $request->file('files', []), $request->user()->id);
        $this->notify($quote, 'New message', trim($data['body']));
        return redirect()->route('account.bulk-quotes.show', $reference)
            ->with('status', 'Your message has been sent to NextPlay.');
    }

    public function decision(Request $request, string $reference): RedirectResponse
    {
        $quote = $this->mine($request, $reference);
        $data = $request->validate(['decision' => ['required', Rule::in(['accept_only', 'create_order'])]]);
        $wasAccepted = in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED], true);
        $oldOrder = $quote->converted_order_id;
        try {
            $quote = $this->quotes->accept($quote, $data['decision'], $data['decision'] === 'create_order' ? $request->user() : null);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['quote' => 'We could not confirm the quotation. Please try again or contact sales.']);
        }
        if (! $wasAccepted || $oldOrder !== $quote->converted_order_id) {
            $this->notify($quote, 'Quote confirmed', $data['decision'] === 'create_order'
                ? 'The registered customer accepted this quotation and created an order.'
                : 'The registered customer accepted this quotation without an order.');
        }
        return redirect()->route('account.bulk-quotes.show', $reference)
            ->with('status', $quote->converted_order_id
                ? 'Quotation confirmed. Your order is available in My Account → Order Center.'
                : 'Quotation confirmed. No order has been created. You can create an order from this quote later.');
    }

    private function notify(BulkQuoteRequest $quote, string $heading, string $message): void
    {
        try {
            $this->emails->bulkQuoteGuestFeedback($quote, $heading, $message);
        } catch (\Throwable $exception) {
            // The DB transaction has committed; email failure must not undo customer work.
            report($exception);
        }
    }

    public function file(Request $request, string $reference, BulkQuoteFile $file): StreamedResponse
    {
        $quote = $this->mine($request, $reference);
        abort_unless((int) $file->bulk_quote_request_id === (int) $quote->id &&
            ($file->category === 'quotation' && (int) $file->version === (int) $quote->quotation_version ||
             $file->category === 'feedback' && $file->bulk_quote_message_id !== null), 404);
        return GuestBulkQuoteController::download($file, $request->boolean('view'));
    }
}
