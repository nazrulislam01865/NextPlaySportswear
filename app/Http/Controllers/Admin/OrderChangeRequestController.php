<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\UpdateChangeRequestRequest;
use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Services\Order\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;

class OrderChangeRequestController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow)
    {
    }

    public function update(
        UpdateChangeRequestRequest $request,
        Order $order,
        OrderChangeRequest $changeRequest,
    ): RedirectResponse {
        abort_unless($changeRequest->order_id === $order->id, 404);

        $this->workflow->resolveChangeRequest(
            $changeRequest,
            $request->user('admin'),
            $request->validated(),
        );

        return back()->with('status', 'Customer request updated.');
    }
}
