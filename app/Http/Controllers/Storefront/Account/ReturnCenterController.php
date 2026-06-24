<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Orders\ExchangeOrderRequest;
use App\Http\Requests\Storefront\Orders\ReturnOrderRequest;
use App\Models\Order;
use App\Models\OrderCreditNote;
use App\Models\OrderRefund;
use App\Models\OrderReturnAttachment;
use App\Models\OrderReturnRequest;
use App\Services\Order\OrderPdfService;
use App\Services\Order\OrderWorkflowService;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReturnCenterController extends Controller
{
    public function __construct(private readonly CustomerAccountService $accounts, private readonly OrderWorkflowService $workflow, private readonly OrderPdfService $pdf) {}

    public function create(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);
        if (! $order->canRequestReturn()) return redirect()->route('account.orders.show', $order)->withErrors(['order' => 'This order is not currently eligible for return.']);
        return $this->view('storefront.account.returns.create', $request, ['order' => $order->load('items.returnItems.returnRequest')], 'Return Request');
    }

    public function store(ReturnOrderRequest $request, Order $order): RedirectResponse
    {
        $return = $this->workflow->createReturnRequest($order, $request->user(), $request->validated(), 'return');
        return redirect()->route('account.returns.show', $return)->with('status', 'Return request '.$return->return_number.' was submitted.');
    }

    public function createExchange(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);
        if (! $order->canRequestExchange()) return redirect()->route('account.orders.show', $order)->withErrors(['order' => 'This order is not currently eligible for exchange.']);
        return $this->view('storefront.account.returns.exchange', $request, ['order' => $order->load('items.returnItems.returnRequest')], 'Exchange Request');
    }

    public function storeExchange(ExchangeOrderRequest $request, Order $order): RedirectResponse
    {
        $exchange = $this->workflow->createReturnRequest($order, $request->user(), $request->validated(), 'exchange');
        return redirect()->route('account.returns.show', $exchange)->with('status', 'Exchange request '.$exchange->return_number.' was submitted.');
    }

    public function index(Request $request): View
    {
        $returns = $request->user()->orderReturnRequests()->with(['order','items.orderItem','refunds'])->paginate(12);
        return $this->view('storefront.account.returns.index', $request, compact('returns'), 'Return History');
    }

    public function show(Request $request, OrderReturnRequest $returnRequest): View
    {
        $this->authorize('view', $returnRequest);
        return $this->view('storefront.account.returns.show', $request, ['returnRequest' => $returnRequest->load(['order','items.orderItem','attachments','refunds.creditNote'])], 'Return Details & Status');
    }

    public function downloadAttachment(Request $request, OrderReturnAttachment $attachment): Response
    {
        $this->authorize('view', $attachment);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function refund(Request $request, OrderRefund $refund): View
    {
        $this->authorize('view', $refund);
        return $this->view('storefront.account.returns.refund', $request, ['refund' => $refund->load(['order','returnRequest','creditNote'])], 'Refund Status');
    }

    public function creditNote(Request $request, OrderCreditNote $creditNote): View
    {
        $this->authorize('view', $creditNote);
        return $this->view('storefront.account.returns.credit-note', $request, [
            'creditNote' => $creditNote->load(['order','refund']),
            'downloadUrl' => URL::temporarySignedRoute('account.credit-notes.download', now()->addMinutes(10), ['creditNote' => $creditNote]),
        ], 'Credit Note');
    }

    public function downloadCreditNote(Request $request, OrderCreditNote $creditNote): Response
    {
        $this->authorize('view', $creditNote);
        return response($this->pdf->creditNote($creditNote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="credit-note-'.$creditNote->credit_note_number.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function view(string $view, Request $request, array $data, string $title): View
    {
        return view($view, array_merge($data, [
            'account' => $this->accounts->dashboard($request->user()),
            'navigation' => $this->accounts->accountNavigation(),
            'seo' => ['title' => $title.' | NextPlay Sportswear', 'description' => 'Secure returns, exchanges, refunds, and credit-note management.', 'robots' => 'noindex, nofollow'],
        ]));
    }
}
