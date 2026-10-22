<?php

namespace App\Jobs\Integrations;

use App\Models\ArtworkProof;
use App\Models\ArtworkProofMessage;
use App\Services\Integrations\FlowTrack\FlowTrackClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Idempotent, server-only outbox of customer decisions and messages. */
final class SyncArtworkReviewToFlowTrack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;
    public array $backoff = [2, 5, 15, 45, 120];

    public function __construct(public int $proofId, public string $kind, public ?int $messageId = null)
    {
        $this->onConnection(config('flowtrack.queue.connection', config('queue.default')));
        $this->onQueue(config('flowtrack.queue.name', 'integrations'));
        $this->afterCommit();
    }

    public function handle(FlowTrackClient $client): void
    {
        $proof = ArtworkProof::query()->with('order')->findOrFail($this->proofId);
        if (! $proof->flowtrack_job_id || ! $proof->flowtrack_document_id) return;
        $payload = [
            'flow_job_id' => (int) $proof->flowtrack_job_id,
            'source_order_id' => (int) $proof->order_id,
            'proof_id' => (int) $proof->id,
            'document_id' => (int) $proof->flowtrack_document_id,
            'revision_id' => (int) $proof->flowtrack_revision_id,
        ];
        if ($this->kind === 'message') {
            $message = ArtworkProofMessage::query()->where('artwork_proof_id', $proof->id)->findOrFail($this->messageId);
            if ($message->sender_role !== 'customer') return;
            $client->sendArtworkReview($payload + [
                'event' => 'message', 'message_id' => (int) $message->id, 'message' => $message->message,
            ]);
            return;
        }

        if ($proof->status === ArtworkProof::REVISION
            && $proof->customer_feedback === 'This review round was reopened for artwork changes.') return;

        $remaining = ArtworkProof::query()->current()->where('artwork_proofs.flowtrack_job_id', $proof->flowtrack_job_id)
            ->where('artwork_proofs.flowtrack_revision_id', $proof->flowtrack_revision_id)
            ->where('artwork_proofs.status', '!=', ArtworkProof::APPROVED)->exists();
        $client->sendArtworkReview($payload + [
            'event' => 'decision',
            'decision' => $proof->status,
            'all_approved' => ! $remaining,
            'feedback' => (string) ($proof->customer_feedback ?? ''),
            'change_types' => $proof->change_types ?? [],
            'priority' => $proof->priority ?? 'normal',
            'has_reference' => filled($proof->reference_path),
            'reference_name' => $proof->reference_path ? basename((string) $proof->reference_name) : null,
        ]);
    }
}
