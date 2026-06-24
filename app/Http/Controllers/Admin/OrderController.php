<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\UpdateOrderRequest;
use App\Models\Order;
use App\Services\Order\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
