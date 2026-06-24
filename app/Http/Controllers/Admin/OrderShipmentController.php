<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\StoreShipmentRequest;
use App\Http\Requests\Admin\Orders\UpdateShipmentRequest;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Services\Order\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;

class OrderShipmentController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow)
    {
    }

    public function store(StoreShipmentRequest $request, Order $order): RedirectResponse
    {
        $shipment = $this->workflow->createShipment($order, $request->user('admin'), $request->validated());

        return back()->with('status', 'Shipment '.$shipment->shipment_number.' created.');
    }

    public function update(
        UpdateShipmentRequest $request,
        Order $order,
        OrderShipment $shipment,
    ): RedirectResponse {
        abort_unless($shipment->order_id === $order->id, 404);

        $this->workflow->updateShipment($shipment, $request->user('admin'), $request->validated());

        return back()->with('status', 'Shipment '.$shipment->shipment_number.' updated.');
    }
}
