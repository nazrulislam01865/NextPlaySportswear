<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtworkProof;
use App\Services\Email\TransactionalEmailManager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Artwork\ArtworkProofService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArtworkProofController extends Controller
{
    public function __construct(
        private readonly ArtworkProofService $proofs,
        private readonly TransactionalEmailManager $emails,
    )
    {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user('admin')?->canAdmin('orders.view'), 403);

        // Latest proof per order item: a review queue, not a history dump.
        $query = ArtworkProof::query()->current()
            ->with([
                'order:id,order_number,customer_name,customer_email,status,payment_status',
                'orderItem:id,order_id,product_name,quantity',
            ]);

        $status = (string) $request->query('status', '');
        if (in_array($status, [ArtworkProof::PENDING, ArtworkProof::REVISION, ArtworkProof::APPROVED, ArtworkProof::REJECTED], true)) {
            $query->where('artwork_proofs.status', $status);
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('artwork_proofs.design_name', 'like', '%'.$search.'%')
                    ->orWhereHas('order', fn ($orders) => $orders
                        ->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhere('customer_email', 'like', '%'.$search.'%'))
                    ->orWhereHas('orderItem', fn ($items) => $items
                        ->where('product_name', 'like', '%'.$search.'%'));
            });
        }

        return view('admin.artwork-approvals.index', [
            'proofs' => $query->orderByDesc('artwork_proofs.created_at')
                ->orderByDesc('artwork_proofs.id')
                ->paginate($this->adminPerPage(20))->withQueryString(),
            'canManageOrders' => (bool) $request->user('admin')?->canManageOrders(),
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->checkPermission($request);
        $data = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'design_name' => ['nullable', 'string', 'max:190'],
            'designer_notes' => ['nullable', 'string', 'max:3000'],
            'proof_file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:20480'],
        ]);
        $item = OrderItem::query()->where('order_id', $order->id)->findOrFail($data['order_item_id']);
        $proof = $this->proofs->publish($order, $item, $request->file('proof_file'), $data['design_name'] ?? null, $data['designer_notes'] ?? null);

        $this->emails->artworkProofPublished($proof);

        return back()->with('status', 'Artwork proof v'.$proof->version.' published to the customer account.');
    }

    public function file(Request $request, Order $order, ArtworkProof $proof): StreamedResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.view'), 403);
        abort_unless($proof->order_id === $order->id, 404);
        return $this->proofs->stream($proof, $request->query('type') === 'reference' ? 'reference' : 'proof', $request->boolean('download'));
    }

    public function reply(Request $request, Order $order, ArtworkProof $proof): RedirectResponse
    {
        $this->checkPermission($request);
        abort_unless($proof->order_id === $order->id, 404);
        $data = $request->validate(['message' => ['required', 'string', 'min:5', 'max:2000']]);
        $proof->messages()->create(['sender_role' => 'designer', 'message' => $data['message']]);
        $this->emails->artworkProofMessage($proof, false);

        return back()->with('status', 'Reply saved for the customer.');
    }

    private function checkPermission(Request $request): void
    {
        abort_unless($request->user('admin')?->canManageOrders(), 403, 'Order management permission is required.');
    }
}
