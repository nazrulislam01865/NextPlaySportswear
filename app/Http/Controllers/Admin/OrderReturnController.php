<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\UpdateReturnRequest;
use App\Models\OrderReturnAttachment;
use App\Models\OrderReturnRequest;
use App\Services\Order\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderReturnController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow)
    {
    }

    public function index(Request $request): View
    {
        $returns = OrderReturnRequest::query()->with(['order', 'user']);

        if ($status = $request->query('status')) {
            if (array_key_exists($status, config('commerce.return_statuses', []))) {
                $returns->where('status', $status);
            }
        }

        if ($type = $request->query('type')) {
            if (in_array($type, ['return', 'exchange'], true)) {
                $returns->where('type', $type);
            }
        }

        return view('admin.returns.index', [
            'returns' => $returns->latest('requested_at')->paginate(25)->withQueryString(),
            'returnStatuses' => config('commerce.return_statuses', []),
        ]);
    }

    public function show(OrderReturnRequest $returnRequest): View
    {
        $allowedStatuses = config(
            'commerce.return_status_transitions.'.$returnRequest->status,
            [$returnRequest->status],
        );

        return view('admin.returns.show', [
            'returnRequest' => $returnRequest->load([
                'order.items',
                'user',
                'items.orderItem',
                'attachments',
                'refunds.creditNote',
            ]),
            'returnStatuses' => collect(config('commerce.return_statuses', []))
                ->only($allowedStatuses)
                ->all(),
            'refundStatuses' => config('commerce.refund_statuses', []),
        ]);
    }

    public function downloadAttachment(
        OrderReturnRequest $returnRequest,
        OrderReturnAttachment $attachment,
    ): Response {
        abort_unless($attachment->order_return_request_id === $returnRequest->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function update(
        UpdateReturnRequest $request,
        OrderReturnRequest $returnRequest,
    ): RedirectResponse {
        $this->workflow->updateReturn(
            $returnRequest,
            $request->user('admin'),
            $request->validated(),
        );

        return back()->with('status', 'Return or exchange updated.');
    }
}
