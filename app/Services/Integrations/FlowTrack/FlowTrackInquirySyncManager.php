<?php

namespace App\Services\Integrations\FlowTrack;

use App\Jobs\Integrations\SyncBulkQuoteToFlowTrack;
use App\Models\BulkQuoteRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FlowTrackInquirySyncManager
{
    public function __construct(
        private readonly FlowTrackClient $client,
    ) {
    }

    /**
     * Storefront quote creation must succeed even when FlowTrack is temporarily
     * unavailable. The local BulkQuoteRequest is always the source record.
     */
    public function dispatch(BulkQuoteRequest $quote): void
    {
        if (! (bool) config('flowtrack.enabled')) {
            $this->saveSyncState($quote, [
                'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_DISABLED,
                'flowtrack_sync_error' => null,
            ]);
            return;
        }

        if ((bool) config('flowtrack.queue.enabled')) {
            try {
                $this->saveSyncState($quote, [
                    'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_PENDING,
                    'flowtrack_sync_error' => null,
                ]);

                SyncBulkQuoteToFlowTrack::dispatch($quote->id)
                    ->onConnection((string) config('flowtrack.queue.connection', 'database'))
                    ->onQueue((string) config('flowtrack.queue.name', 'integrations'))
                    ->afterCommit();

                Log::info('flowtrack.inquiry_sync_queued', [
                    'bulk_quote_id' => $quote->id,
                    'reference' => $quote->reference,
                ]);
            } catch (Throwable $exception) {
                $this->markFailure($quote, $exception);
                Log::error('flowtrack.inquiry_sync_queue_failed', [
                    'bulk_quote_id' => $quote->id,
                    'reference' => $quote->reference,
                    'message' => mb_substr($exception->getMessage(), 0, 1500),
                ]);
            }

            return;
        }

        try {
            $this->syncNow($quote);
        } catch (Throwable $exception) {
            Log::error('flowtrack.inquiry_sync_failed', [
                'bulk_quote_id' => $quote->id,
                'reference' => $quote->reference,
                'exception' => $exception::class,
                'message' => mb_substr($exception->getMessage(), 0, 1500),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function syncNow(BulkQuoteRequest $quote, bool $markFailureImmediately = true): array
    {
        $quote = BulkQuoteRequest::query()->findOrFail($quote->id);
        $this->incrementAttemptsWithoutTouchingSourceTimestamp($quote);
        $this->saveSyncState($quote, [
            'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_SYNCING,
            'flowtrack_last_attempt_at' => now(),
            'flowtrack_sync_error' => null,
        ]);

        try {
            $response = $this->client->sendInquiry($quote);
        } catch (Throwable $exception) {
            if ($markFailureImmediately) {
                $this->markFailure($quote, $exception);
            } else {
                $this->saveSyncState($quote, [
                    'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_SYNCING,
                    'flowtrack_sync_error' => mb_substr($exception->getMessage(), 0, 5000),
                ]);
            }

            throw $exception;
        }

        $this->saveSyncState($quote, [
            'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_SYNCED,
            'flowtrack_inquiry_id' => filled(data_get($response, 'data.inquiry_id'))
                ? (int) data_get($response, 'data.inquiry_id')
                : $quote->flowtrack_inquiry_id,
            'flowtrack_inquiry_number' => filled(data_get($response, 'data.inquiry_number'))
                ? (string) data_get($response, 'data.inquiry_number')
                : $quote->flowtrack_inquiry_number,
            'flowtrack_synced_at' => now(),
            'flowtrack_sync_error' => null,
            'flowtrack_response' => $response,
        ]);

        $quote->refresh();

        Log::info('flowtrack.inquiry_sync_succeeded', [
            'bulk_quote_id' => $quote->id,
            'reference' => $quote->reference,
            'flowtrack_inquiry_id' => $quote->flowtrack_inquiry_id,
            'flowtrack_inquiry_number' => $quote->flowtrack_inquiry_number,
        ]);

        return $response;
    }

    public function recordPermanentFailure(int $bulkQuoteId, Throwable $exception): void
    {
        $quote = BulkQuoteRequest::query()->find($bulkQuoteId);

        if ($quote instanceof BulkQuoteRequest) {
            $this->markFailure($quote, $exception);
        }

        Log::error('flowtrack.inquiry_sync_failed_permanently', [
            'bulk_quote_id' => $bulkQuoteId,
            'exception' => $exception::class,
            'message' => mb_substr($exception->getMessage(), 0, 1500),
        ]);
    }

    private function markFailure(BulkQuoteRequest $quote, Throwable $exception): void
    {
        $this->saveSyncState($quote, [
            'flowtrack_sync_status' => BulkQuoteRequest::FLOWTRACK_SYNC_FAILED,
            'flowtrack_last_attempt_at' => $quote->flowtrack_last_attempt_at ?: now(),
            'flowtrack_sync_error' => mb_substr($exception->getMessage(), 0, 5000),
        ]);
    }

    /** @param array<string,mixed> $attributes */
    private function saveSyncState(BulkQuoteRequest $quote, array $attributes): void
    {
        $timestamps = $quote->timestamps;
        $quote->timestamps = false;

        try {
            $quote->forceFill($attributes)->saveQuietly();
        } finally {
            $quote->timestamps = $timestamps;
        }
    }

    private function incrementAttemptsWithoutTouchingSourceTimestamp(BulkQuoteRequest $quote): void
    {
        $timestamps = $quote->timestamps;
        $quote->timestamps = false;

        try {
            $quote->increment('flowtrack_sync_attempts');
        } finally {
            $quote->timestamps = $timestamps;
        }
    }
}
