<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkQuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Authenticated FlowTracker milestone updates, independent of quotation publication. */
final class FlowTrackBulkQuoteTaskProgressController extends Controller
{
    public function __invoke(Request $request, BulkQuoteRequest $bulkQuote): JsonResponse
    {
        if (! $request->isJson()) return response()->json(['ok' => false, 'message' => 'JSON required'], 415);

        $data = $request->validate([
            'flowtrack_inquiry_id' => ['required', 'integer', 'min:1'],
            'change_request_id' => ['nullable', 'integer', 'min:0'],
            'review_at' => ['nullable', 'date'],
            'prepared_at' => ['nullable', 'date'],
            'sent_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($bulkQuote, $data): void {
            $quote = BulkQuoteRequest::query()->whereKey($bulkQuote->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $quote->flowtrack_inquiry_id === (int) $data['flowtrack_inquiry_id'], 404);
            abort_if((int) $quote->last_change_request_id !== (int) ($data['change_request_id'] ?? 0), 409, 'Stale quotation progress.');
            $progress = [
                'review_at' => $data['review_at'] ?? null,
                'prepared_at' => $data['prepared_at'] ?? null,
                'sent_at' => $data['sent_at'] ?? null,
            ];
            if ((array) ($quote->flowtrack_task_progress ?? []) !== $progress) {
                $quote->forceFill(['flowtrack_task_progress' => $progress])->saveQuietly();
            }
        });

        return response()->json(['ok' => true]);
    }
}
