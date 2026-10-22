<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtworkProof;
use App\Models\Order;
use App\Services\Artwork\ArtworkProofService;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Token-authenticated inbound artwork from the *linked* FlowTracker Order. */
final class FlowTrackArtworkReviewController extends Controller
{
    public function publish(Request $request, ArtworkProofService $proofs, TransactionalEmailManager $emails): JsonResponse
    {
        $data = $request->validate([
            'flow_job_id' => ['required', 'integer', 'min:1'],
            'source_order_id' => ['required', 'integer', 'min:1'],
            'order_number' => ['required', 'string', 'max:120'],
            'revision_id' => ['required', 'integer', 'min:0'],
            'documents' => ['required', 'array', 'min:1', 'max:25'],
            'documents.*.document_id' => ['required', 'integer', 'min:1', 'distinct'],
            'documents.*.name' => ['required', 'string', 'max:190'],
            'documents.*.slot' => ['required', 'integer', 'min:1', 'max:25'],
            'documents.*.order_item_id' => ['nullable', 'integer', 'min:1'],
            'files' => ['required', 'array', 'min:1', 'max:25'],
            'files.*' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:20480'],
        ]);
        abort_unless(count($data['documents']) === count($data['files']), 422, 'Every artwork must include a file.');
        $order = Order::query()->whereKey($data['source_order_id'])
            ->where('order_number', $data['order_number'])
            ->where('flowtrack_job_id', (string) $data['flow_job_id'])->firstOrFail();
        abort_if(in_array($order->status, ['cancelled', 'completed', 'shipped', 'delivered'], true), 422, 'This order cannot receive artwork.');
        $items = $order->items()->orderBy('id')->get();
        // Assign artwork only to the exact NPS line provided by FlowTracker.
        // The single-line fallback supports front/back and multiple layouts,
        // while ambiguous multi-product orders require explicit item ids.
        $itemById = $items->keyBy('id');
        $single = $items->count() === 1 ? $items->first() : null;
        foreach ($data['documents'] as $document) {
            $itemId = (int) ($document['order_item_id'] ?? 0);
            if (! $single && ! $itemId) {
                throw ValidationException::withMessages(['documents' => 'For multi-product orders, each artwork must specify its NPS order item id.']);
            }
            abort_unless(($single && (! $itemId || $itemId === (int) $single->id)) || $itemById->has($itemId), 422,
                'An artwork references an item outside this order.');
        }
        $published = [];
        foreach ($data['documents'] as $i => $document) {
            $item = $itemById->get((int) ($document['order_item_id'] ?? 0)) ?: $single;
            $result = $proofs->publishFromFlowTrack(
                $order, $item, $request->file('files')[$i],
                (int) $data['flow_job_id'], (int) $document['document_id'],
                (int) $data['revision_id'], $document['name'], (int) $document['slot'],
            );
            $published[] = $result['proof'];
            if ($result['created']) {
                try {
                    $emails->artworkProofPublished($result['proof']);
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::warning('nextplay.artwork_proof_notification_failed', [
                        'proof_id' => $result['proof']->id, 'error' => $exception->getMessage(),
                    ]);
                }
            }
        }
        \Illuminate\Support\Facades\Log::info('nextplay.artwork_proofs_received', [
            'order_id' => $order->id,
            'flow_job_id' => (int) $data['flow_job_id'],
            'proof_ids' => array_map(fn ($proof) => $proof->id, $published),
        ]);
        return response()->json(['ok' => true, 'proof_ids' => array_map(fn ($p) => $p->id, $published)]);
    }

    public function staffMessage(Request $request, ArtworkProof $proof): JsonResponse
    {
        $data = $request->validate([
            'flow_job_id' => ['required', 'integer', 'min:1'],
            'activity_id' => ['required', 'integer', 'min:1'],
            'message' => ['required', 'string', 'min:2', 'max:2000'],
        ]);
        abort_unless((int) $proof->flowtrack_job_id === (int) $data['flow_job_id'], 404);
        $message = DB::transaction(function () use ($proof, $data) {
            $existing = \App\Models\ArtworkProofMessage::query()
                ->where('flowtrack_activity_id', $data['activity_id'])->first();
            if ($existing) {
                abort_unless((int) $existing->artwork_proof_id === (int) $proof->id, 409);
                return $existing;
            }
            return $proof->messages()->create([
                'sender_role' => 'designer', 'message' => $data['message'],
                'flowtrack_activity_id' => $data['activity_id'],
            ]);
        }, 3);
        return response()->json(['ok' => true, 'message_id' => $message->id]);
    }

    public function reference(Request $request, ArtworkProof $proof): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->integer('flow_job_id') > 0
            && (int) $proof->flowtrack_job_id === $request->integer('flow_job_id')
            && $proof->reference_path, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($proof->reference_path), 404);
        return $disk->download($proof->reference_path, basename($proof->reference_name ?: 'reference'));
    }
}
