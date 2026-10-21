<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Integrations\TransferFlowTrackQuoteOffer;
use App\Models\BulkQuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** Authenticated offer staging; the verified transfer job publishes through the shared service. */
final class FlowTrackBulkQuoteOfferController extends Controller
{
    public function __invoke(Request $request, BulkQuoteRequest $bulkQuote): JsonResponse
    {
        if (! $request->isJson()) return response()->json(['ok' => false, 'message' => 'JSON required'], 415);

        $data = $request->validate([
            'flowtrack_inquiry_id' => ['required', 'integer', 'min:1'],
            'flowtrack_task_id' => ['required', 'integer', 'min:1'],
            'completed' => ['required', 'boolean'],
            'change_request_id' => ['nullable', 'integer', 'min:0'],
            'files' => ['required', 'array', 'max:8'],
            'files.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'files.*.name' => ['required', 'string', 'max:190'],
            'files.*.mime_type' => ['nullable', 'string', 'max:190'],
            'files.*.size' => ['required', 'integer', 'min:1', 'max:10485760'],
            'files.*.checksum_sha256' => ['required', 'regex:/^[a-fA-F0-9]{64}$/'],
            'files.*.download_path' => ['required', 'string', 'max:250'],
        ]);
        if ($data['completed'] && $data['files'] === []) {
            return response()->json(['ok' => false, 'message' => 'A completed offer needs at least one quotation file.'], 422);
        }
        if (! $data['completed'] && $data['files'] !== []) {
            return response()->json(['ok' => false, 'message' => 'A reopened task cannot publish offer files.'], 422);
        }
        $inquiryId = (int) $data['flowtrack_inquiry_id'];
        $taskId = (int) $data['flowtrack_task_id'];
        foreach ($data['files'] as $file) {
            $expected = '/api/integrations/nextplay/bulk-orders/'.$inquiryId.'/offer-tasks/'.$taskId.'/files/'.(int) $file['id'];
            $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
            if (! hash_equals($expected, $file['download_path'])
                || ! in_array($extension, ['pdf', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'], true)) {
                return response()->json(['ok' => false, 'message' => 'Invalid offer file information.'], 422);
            }
        }

        $manifestHash = hash('sha256', json_encode([$taskId, $data['completed'], $data['files']], JSON_THROW_ON_ERROR));
        DB::transaction(function () use ($bulkQuote, $inquiryId, $manifestHash, $data): void {
            $quote = BulkQuoteRequest::query()->whereKey($bulkQuote->id)->lockForUpdate()->firstOrFail();
            if ((int) $quote->last_change_request_id !== (int) ($data['change_request_id'] ?? 0)) {
                abort(409, 'This offer belongs to an earlier quotation revision.');
            }
            if ((int) $quote->flowtrack_inquiry_id !== $inquiryId) {
                abort(404, 'The FlowTracker inquiry link does not match this quotation.');
            }
            if (! hash_equals((string) $quote->flowtrack_offer_manifest_hash, $manifestHash)) {
                $quote->forceFill([
                    'flowtrack_offer_manifest_hash' => $manifestHash,
                    'flowtrack_offer_ready_hash' => null,
                    'flowtrack_offer_file_count' => count($data['files']),
                ])->saveQuietly();
            } else {
                // An identical manifest is not proof that its private files are still
                // present (for example, after a project-directory replacement).
                // Recheck only when FlowTracker sends an event, never on a timer.
                $sourceKeys = array_map(
                    fn (array $file): string => 'flowtrack-offer:'.$inquiryId.':'.(int) $data['flowtrack_task_id'].':'.(int) $file['id'],
                    $data['files']
                );
                $storedFiles = $quote->quoteFiles()->where('category', 'flowtrack_offer')
                    ->whereIn('source_key', $sourceKeys)->get();
                $filesPresent = $storedFiles->count() === count($sourceKeys)
                    && $storedFiles->every(fn ($file) => Storage::disk('local')->exists($file->path)
                        && Storage::disk('local')->size($file->path) === (int) $file->size);
                if (! $filesPresent || (int) $quote->flowtrack_offer_file_count !== count($data['files'])) {
                    $quote->forceFill([
                        'flowtrack_offer_ready_hash' => null,
                        'flowtrack_offer_file_count' => count($data['files']),
                    ])->saveQuietly();
                }
            }
        });

        TransferFlowTrackQuoteOffer::dispatch($bulkQuote->id, $inquiryId, $taskId, $data['files'], $manifestHash)
            ->onConnection((string) config('flowtrack.queue.connection', 'database'))
            ->onQueue((string) config('flowtrack.queue.name', 'integrations'))
            ->afterCommit();

        Log::info('flowtrack.offer_manifest_received', [
            'quote_id' => (int) $bulkQuote->id,
            'inquiry_id' => $inquiryId,
            'task_id' => $taskId,
            'files' => count($data['files']),
        ]);

        return response()->json(['ok' => true, 'queued_files' => count($data['files'])]);
    }
}
