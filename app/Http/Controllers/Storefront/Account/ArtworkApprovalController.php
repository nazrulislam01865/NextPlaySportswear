<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Models\ArtworkProof;
use App\Services\Artwork\ArtworkProofService;
use App\Services\Email\TransactionalEmailManager;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArtworkApprovalController extends Controller
{
    public function __construct(
        private readonly ArtworkProofService $proofs,
        private readonly CustomerAccountService $accounts,
        private readonly TransactionalEmailManager $emails,
    ) {
    }

    public function index(Request $request): View
    {
        $query = $this->proofs->currentForCustomer($request->user()->id);
        $counts = (clone $query)->reorder()->select('artwork_proofs.status')->selectRaw('COUNT(*) AS total')
            ->groupBy('artwork_proofs.status')->pluck('total', 'status');
        $status = $request->query('status');
        if (in_array($status, [ArtworkProof::PENDING, ArtworkProof::APPROVED, ArtworkProof::REVISION, ArtworkProof::REJECTED], true)) {
            $query->where('artwork_proofs.status', $status);
        }

        return $this->page('storefront.account.artwork.index', $request, [
            'proofs' => $query->with(['order:id,order_number,status', 'orderItem:id,product_name,quantity,image_url'])
                ->orderByDesc('artwork_proofs.created_at')->orderByDesc('artwork_proofs.id')
                ->paginate(8)->withQueryString(),
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, ArtworkProof $proof): View
    {
        $this->assertOwner($request, $proof);
        $proof->load(['order', 'orderItem']);
        $versions = ArtworkProof::query()->where('order_item_id', $proof->order_item_id)
            ->with('messages')->latest('version')->get();
        $isCurrent = $versions->first()?->id === $proof->id;
        // Replies belong to a proof version; retain the conversation when a newer proof is published.
        $messages = $versions->flatMap(fn (ArtworkProof $version) => $version->messages)
            ->sortBy(fn ($message) => sprintf('%s-%010d', $message->created_at?->format('Y-m-d H:i:s.u'), $message->id))
            ->values();

        return $this->page('storefront.account.artwork.show', $request, compact('proof', 'versions', 'isCurrent', 'messages'));
    }

    public function changes(Request $request, ArtworkProof $proof): View|RedirectResponse
    {
        $this->assertOwner($request, $proof);
        if (! $this->canReview($proof)) {
            return redirect()->route('account.artwork.show', $proof)->withErrors(['proof' => 'This proof is no longer awaiting review.']);
        }
        $proof->load(['order', 'orderItem']);
        return $this->page('storefront.account.artwork.changes', $request, [
            'proof' => $proof,
            'remaining' => $this->remainingRevisions($proof),
        ]);
    }

    public function approve(Request $request, ArtworkProof $proof): RedirectResponse
    {
        $this->assertOwner($request, $proof);
        $request->validate(['confirm' => ['required', 'accepted']]);
        $updated = $this->proofs->decide($proof, ArtworkProof::APPROVED);
        $this->emails->artworkProofReviewed($updated);

        return redirect()->route('account.artwork.approved', $proof)->with('status', 'Artwork approved successfully.');
    }

    public function requestChanges(Request $request, ArtworkProof $proof): RedirectResponse
    {
        $this->assertOwner($request, $proof);
        $validated = $request->validate([
            'feedback' => ['required', 'string', 'min:10', 'max:500'],
            'change_types' => ['sometimes', 'array', 'max:5'],
            'change_types.*' => [Rule::in(['color', 'logo', 'text', 'layout', 'other'])],
            'priority' => ['required', Rule::in(['normal', 'urgent'])],
            'reference_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:10240'],
        ]);
        $updated = $this->proofs->decide($proof, ArtworkProof::REVISION, $validated, $request->file('reference_file'));
        $this->emails->artworkProofReviewed($updated);

        return redirect()->route('account.artwork.show', $proof)->with('status', 'Your revision request was sent to the design team.');
    }

    public function reject(Request $request, ArtworkProof $proof): RedirectResponse
    {
        $this->assertOwner($request, $proof);
        $validated = $request->validate(['feedback' => ['required', 'string', 'min:10', 'max:500']]);
        $updated = $this->proofs->decide($proof, ArtworkProof::REJECTED, $validated);
        $this->emails->artworkProofReviewed($updated);

        return redirect()->route('account.artwork.show', $proof)->with('status', 'Proof rejected. Your feedback is saved.');
    }

    public function message(Request $request, ArtworkProof $proof): RedirectResponse
    {
        $this->assertOwner($request, $proof);
        $validated = $request->validate(['message' => ['required', 'string', 'min:5', 'max:2000']]);
        $proof->messages()->create(['sender_role' => 'customer', 'message' => $validated['message']]);
        $this->emails->artworkProofMessage($proof, true);

        return redirect()->to(route('account.artwork.show', $proof).'?message=1#designer-messages')->with('status', 'Message sent to the design team.');
    }

    public function approved(Request $request, ArtworkProof $proof): View|RedirectResponse
    {
        $this->assertOwner($request, $proof);
        if ($proof->status !== ArtworkProof::APPROVED) {
            return redirect()->route('account.artwork.show', $proof);
        }
        $proof->load(['order', 'orderItem']);
        $otherProofsPending = ArtworkProof::query()->current()
            ->where('artwork_proofs.order_id', $proof->order_id)
            ->where('artwork_proofs.status', '!=', ArtworkProof::APPROVED)
            ->exists();

        return $this->page('storefront.account.artwork.approved', $request, compact('proof', 'otherProofsPending'));
    }

    public function file(Request $request, ArtworkProof $proof): StreamedResponse
    {
        $this->assertOwner($request, $proof);
        $type = $request->query('type') === 'reference' ? 'reference' : 'proof';
        return $this->proofs->stream($proof, $type, $request->boolean('download'));
    }

    private function assertOwner(Request $request, ArtworkProof $proof): void
    {
        $proof->loadMissing('order');
        $this->authorize('view', $proof->order);
    }

    private function canReview(ArtworkProof $proof): bool
    {
        return $proof->status === ArtworkProof::PENDING
            && $proof->version === (int) ArtworkProof::query()->where('order_item_id', $proof->order_item_id)->max('version');
    }

    private function remainingRevisions(ArtworkProof $proof): ?int
    {
        $max = max(0, (int) config('storefront.artwork.max_revisions', 2));
        if ($max === 0) {
            return null;
        }
        $used = ArtworkProof::query()->where('order_item_id', $proof->order_item_id)
            ->where('status', ArtworkProof::REVISION)->count();
        return max(0, $max - $used);
    }

    private function page(string $template, Request $request, array $data): View
    {
        return view($template, array_merge($data, [
            'seo' => ['title' => 'Artwork Approvals | NextPlay Sportswear', 'robots' => 'noindex, nofollow'],
            'account' => $this->accounts->sidebarData($request->user()),
            'navigation' => $this->accounts->accountNavigation(),
        ]));
    }
}
