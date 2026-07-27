<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow)
    {
    }

    public function index(Request $request): View
    {
        $orders = Order::query()->with('user');

        if ($search = trim((string) $request->query('q'))) {
            $orders->where(function ($query) use ($search): void {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_email', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%');
            });
        }

        if ($status = $request->query('status')) {
            if (array_key_exists($status, config('commerce.order_statuses', []))) {
                $orders->where('status', $status);
            }
        }

        if ($paymentStatus = $request->query('payment_status')) {
            if (array_key_exists($paymentStatus, config('commerce.payment_statuses', []))) {
                $orders->where('payment_status', $paymentStatus);
            }
        }

        return view('admin.orders.index', [
            'orders' => $orders->latest('placed_at')->paginate(25)->withQueryString(),
            'orderStatuses' => config('commerce.order_statuses', []),
            'paymentStatuses' => config('commerce.payment_statuses', []),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order->load([
                'user',
                'items',
                'payments',
                'histories.actor',
                'shipments.items.orderItem',
                'changeRequests.user',
                'returnRequests.items.orderItem',
                'returnRequests.refunds.creditNote',
                'downloads',
            ]),
            'orderStatuses' => config('commerce.order_statuses', []),
            'paymentStatuses' => config('commerce.payment_statuses', []),
            'fulfillmentStatuses' => config('commerce.fulfillment_statuses', []),
            'shipmentStatuses' => config('commerce.shipment_statuses', []),
        ]);
    }

    public function artwork(Request $request, Order $order, OrderItem $orderItem, int $artworkIndex): StreamedResponse
    {
        abort_unless((int) $orderItem->order_id === (int) $order->id, 404);

        $artwork = $orderItem->artworkFiles()[$artworkIndex] ?? null;
        abort_unless(is_array($artwork) && filled($artwork['path'] ?? null), 404);

        $path = (string) $artwork['path'];
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404, 'The uploaded artwork file could not be found.');

        $originalName = basename((string) ($artwork['original_name'] ?? 'artwork-file'));
        $safeName = str_replace(["\r", "\n", '"'], '', $originalName);
        $mimeType = trim((string) ($artwork['mime_type'] ?? ''));
        if ($mimeType === '' || $mimeType === 'application/octet-stream') {
            $mimeType = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
        }

        $stream = $disk->readStream($path);
        abort_if($stream === false, 404);

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition.'; filename="'.$safeName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:",
        ]);
    }

    public function approve(Request $request, Order $order): RedirectResponse
    {
        $admin = $request->user('admin');
        abort_unless($admin?->canManageOrders(), 403, 'Order management access is required.');

        $this->workflow->approveAfterPayment($order, $admin);

        return back()->with('status', 'Order approved and moved to Design Review.');
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->workflow->updateOrder(
            $order,
            $request->user('admin'),
            $request->validated(),
        );

        return back()->with('status', 'Order status updated.');
    }
}
