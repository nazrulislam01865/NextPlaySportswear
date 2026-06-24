<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Orders\CancelOrderRequest;
use App\Http\Requests\Storefront\Orders\ChangeOrderRequest;
use App\Http\Requests\Storefront\Orders\PayOrderRequest;
use App\Http\Requests\Storefront\Orders\ReorderRequest;
use App\Models\Order;
use App\Models\OrderDownload;
use App\Models\OrderShipment;
use App\Services\Order\OrderPdfService;
use App\Services\Order\OrderWorkflowService;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderCenterController extends Controller
{
    public function __construct(
        private readonly CustomerAccountService $accounts,
        private readonly OrderWorkflowService $workflow,
        private readonly OrderPdfService $pdf,
    ) {
    }

    public function dashboard(Request $request): View
    {
        $query = $request->user()->orders()->with('items');
        $recent = (clone $query)->limit(4)->get();

        return $this->view('storefront.account.orders.dashboard', $request, [
            'orders' => $recent,
            'stats' => [
                'open' => $request->user()->orders()->whereNotIn('status', ['completed','cancelled'])->count(),
                'payment_due' => $request->user()->orders()->whereIn('payment_status', ['pending','failed'])->count(),
                'returns' => $request->user()->orderReturnRequests()->count(),
                'downloads' => OrderDownload::query()->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))->where('is_active', true)->count(),
            ],
        ], 'Customer Orders');
    }

    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->with('items');
        if ($search = trim((string) $request->query('q'))) {
            $orders->where(function ($query) use ($search): void {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhereHas('items', fn ($items) => $items->where('product_name', 'like', '%'.$search.'%'));
            });
        }
        if ($status = $request->query('status')) {
            if (array_key_exists($status, config('commerce.order_statuses', []))) $orders->where('status', $status);
        }
        if ($payment = $request->query('payment_status')) {
            if (array_key_exists($payment, config('commerce.payment_statuses', []))) $orders->where('payment_status', $payment);
        }

        return $this->view('storefront.account.orders.index', $request, [
            'orders' => $orders->paginate(10)->withQueryString(),
            'orderStatuses' => config('commerce.order_statuses', []),
            'paymentStatuses' => config('commerce.payment_statuses', []),
        ], 'My Orders');
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['items','histories','shipments.items.orderItem','changeRequests','returnRequests.items.orderItem','refunds.creditNote','creditNotes','downloads']);

        return $this->view('storefront.account.orders.show', $request, compact('order'), 'Order '.$order->order_number);
    }

    public function pay(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);
        if (! $order->canPay()) return redirect()->route('account.orders.show', $order)->with('status', 'This order does not currently require payment.');

        return $this->view('storefront.account.orders.pay', $request, [
            'order' => $order->load('items'),
            'savedPaymentMethods' => $request->user()->customerPaymentMethods()->orderByDesc('is_default')->get(),
            'retryMode' => false,
        ], 'Pay for Order');
    }

    public function retry(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);
        if (! $order->canPay()) return redirect()->route('account.orders.show', $order)->with('status', 'This order is not eligible for another payment attempt.');

        return $this->view('storefront.account.orders.retry-payment', $request, [
            'order' => $order->load(['items','payments' => fn ($q) => $q->latest()]),
            'savedPaymentMethods' => $request->user()->customerPaymentMethods()->orderByDesc('is_default')->get(),
            'retryMode' => true,
        ], 'Retry Failed Payment');
    }

    public function storePayment(PayOrderRequest $request, Order $order): RedirectResponse
    {
        $payment = $this->workflow->createPaymentAttempt($order, $request->validated());

        return redirect()->route('account.orders.show', $order)->with('status', 'Payment attempt '.$payment->id.' was recorded. Complete payment through the configured secure provider; the order will update only after provider confirmation.');
    }

    public function reorder(Request $request, Order $order): View
    {
        $this->authorize('view', $order);
        return $this->view('storefront.account.orders.reorder', $request, ['order' => $order->load('items')], 'Order Again');
    }

    public function storeReorder(ReorderRequest $request, Order $order): RedirectResponse
    {
        $count = $this->workflow->reorder($order, $request->validated());
        return redirect()->route('cart.index')->with('status', $count.' product selection(s) were added. Review current pricing, availability, sizes, roster, and artwork before checkout.');
    }

    public function cancel(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);
        if (! $order->canRequestCancellation()) return redirect()->route('account.orders.show', $order)->withErrors(['order' => 'Cancellation is no longer available or a request is already pending.']);
        return $this->view('storefront.account.orders.cancel', $request, ['order' => $order->load('items')], 'Cancel Order Request');
    }

    public function storeCancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $change = $this->workflow->createCancellationRequest($order, $request->user(), $request->validated());
        return redirect()->route('account.orders.show', $order)->with('status', 'Cancellation request '.$change->request_number.' was submitted for review.');
    }

    public function change(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);
        if (! $order->canRequestChange()) return redirect()->route('account.orders.show', $order)->withErrors(['order' => 'Changes are no longer available or a request is already pending.']);
        return $this->view('storefront.account.orders.change', $request, ['order' => $order->load('items')], 'Change Order Request');
    }

    public function storeChange(ChangeOrderRequest $request, Order $order): RedirectResponse
    {
        $change = $this->workflow->createChangeRequest($order, $request->user(), $request->validated());
        return redirect()->route('account.orders.show', $order)->with('status', 'Change request '.$change->request_number.' was submitted. Production details are unchanged until approval.');
    }

    public function shipments(Request $request, Order $order): View
    {
        $this->authorize('view', $order);
        return $this->view('storefront.account.orders.shipments', $request, ['order' => $order->load(['items','shipments.items.orderItem'])], 'Partial & Split Shipments');
    }

    public function shipment(Request $request, Order $order, OrderShipment $shipment): View
    {
        $this->authorize('view', $order);
        abort_unless($shipment->order_id === $order->id, 404);
        $this->authorize('view', $shipment);
        return $this->view('storefront.account.orders.shipment', $request, ['order' => $order, 'shipment' => $shipment->load('items.orderItem')], 'Shipment Details');
    }

    public function invoice(Request $request, Order $order): View
    {
        $this->authorize('view', $order);
        return $this->view('storefront.account.orders.invoice', $request, [
            'order' => $order->load('items'),
            'downloadUrl' => URL::temporarySignedRoute('account.orders.invoice.download', now()->addMinutes(10), ['order' => $order]),
        ], 'Secure Invoice');
    }

    public function downloadInvoice(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);
        return response($this->pdf->invoice($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$order->order_number.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloads(Request $request): View
    {
        $downloads = OrderDownload::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()->paginate(20);

        return $this->view('storefront.account.orders.downloads', $request, compact('downloads'), 'Order Downloads');
    }

    public function download(Request $request, OrderDownload $download): BinaryFileResponse
    {
        $this->authorize('download', $download);

        $locked = DB::transaction(function () use ($download): OrderDownload {
            $record = OrderDownload::query()->lockForUpdate()->findOrFail($download->id);
            abort_unless($record->isAvailable(), 410, 'This download is no longer available.');
            abort_unless(Storage::disk('local')->exists($record->file_path), 404);
            $record->increment('download_count');

            return $record;
        });

        return Storage::disk('local')->download($locked->file_path, $locked->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function view(string $view, Request $request, array $data, string $title): View
    {
        return view($view, array_merge($data, [
            'account' => $this->accounts->dashboard($request->user()),
            'navigation' => $this->accounts->accountNavigation(),
            'seo' => ['title' => $title.' | NextPlay Sportswear', 'description' => 'Secure customer order management for payments, shipments, returns, refunds, invoices, and downloads.', 'robots' => 'noindex, nofollow'],
        ]));
    }
}
