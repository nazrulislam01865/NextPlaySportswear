<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreOrderDownloadRequest;
use App\Models\Order;
use App\Models\OrderDownload;
use App\Services\Order\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;

class OrderDownloadController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow)
    {
    }

    public function store(StoreOrderDownloadRequest $request, Order $order): RedirectResponse
    {
        $download = $this->workflow->storeDownload(
            $order,
            $request->user('admin'),
            $request->validated(),
            $request->file('file'),
        );

        return back()->with('status', 'Private download '.$download->title.' added.');
    }

    public function destroy(Order $order, OrderDownload $download): RedirectResponse
    {
        abort_unless($download->order_id === $order->id, 404);

        $this->workflow->deleteDownload($download);

        return back()->with('status', 'Order download removed.');
    }
}
