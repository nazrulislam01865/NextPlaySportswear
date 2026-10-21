<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkQuoteMessage;
use App\Models\BulkQuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Only the existing bearer-authenticated integration can create staff replies. */
final class FlowTrackBulkQuoteMessageController extends Controller
{
    public function __invoke(Request $request, BulkQuoteRequest $bulkQuote): JsonResponse
    {
        if (! $request->isJson()) return response()->json(['ok' => false, 'message' => 'JSON required'], 415);
        $data = $request->validate([
            'flowtrack_inquiry_id' => ['required', 'integer', 'min:1'],
            'flowtrack_message_id' => ['required', 'integer', 'min:1'],
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);
        $message = DB::transaction(function () use ($bulkQuote, $data): BulkQuoteMessage {
            $quote = BulkQuoteRequest::query()->whereKey($bulkQuote->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $quote->flowtrack_inquiry_id === (int) $data['flowtrack_inquiry_id'], 404);
            $existing = BulkQuoteMessage::query()->where('flowtrack_message_id', $data['flowtrack_message_id'])->first();
            if ($existing) {
                abort_unless((int) $existing->bulk_quote_request_id === (int) $quote->id, 409);
                return $existing;
            }
            return $quote->messages()->create([
                'sender' => 'admin', 'actor_id' => null, 'kind' => 'message',
                'body' => trim($data['body']), 'flowtrack_message_id' => (int) $data['flowtrack_message_id'],
            ]);
        }, 3);
        return response()->json(['ok' => true, 'message_id' => $message->id]);
    }
}
