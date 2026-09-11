<?php

namespace App\Services\Integrations\FlowTrack;

use App\Jobs\Integrations\SyncOrderToFlowTrack;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class FlowTrackOrderSyncManager
{
    private ?bool $storageReady = null;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCING = 'syncing';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DISABLED = 'disabled';

    public function __construct(
        private readonly FlowTrackClient $client,
    ) {
    }

    /** @return array<string,string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SYNCING => 'Syncing',
            self::STATUS_SYNCED => 'Synced',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_DISABLED => 'Disabled',
        ];
    }

    /**
     * Start synchronization without ever making FlowTrack availability part of
     * the NextPlay checkout transaction.
     */
    public function dispatch(Order $order): void
    {
        if (! (bool) config('flowtrack.enabled')) {
            $this->saveSyncState($order, [
                'flowtrack_sync_status' => self::STATUS_DISABLED,
                'flowtrack_sync_error' => null,
            ]);
            return;
        }

        if ((bool) config('flowtrack.queue.enabled')) {
            try {
                $this->saveSyncState($order, [
                    'flowtrack_sync_status' => self::STATUS_PENDING,
                    'flowtrack_sync_error' => null,
                ]);

                SyncOrderToFlowTrack::dispatch($order->id)
                    ->onConnection((string) config('flowtrack.queue.connection', 'database'))
                    ->onQueue((string) config('flowtrack.queue.name', 'integrations'))
                    ->afterCommit();

                Log::info('flowtrack.order_sync_queued', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            } catch (Throwable $exception) {
                $this->markFailure($order, $exception);
                $this->logFailure($order, $exception, 'flowtrack.order_sync_queue_failed');
            }

            return;
        }

        try {
            $this->syncNow($order);
        } catch (Throwable $exception) {
            // The NextPlay order already exists at this point. Integration
            // failure must never roll it back or show a false checkout failure.
            $this->logFailure($order, $exception, 'flowtrack.order_sync_failed');
        }
    }

    /** @return array<string,mixed> */
    public function syncNow(Order $order, bool $markFailureImmediately = true): array
    {
        $order = Order::query()->withTrashed()->findOrFail($order->id);

        $this->incrementAttemptsWithoutTouchingSourceTimestamp($order);
        $this->saveSyncState($order, [
            'flowtrack_sync_status' => self::STATUS_SYNCING,
            'flowtrack_last_attempt_at' => now(),
            'flowtrack_sync_error' => null,
        ]);

        try {
            $response = $this->client->sendOrder($order);
        } catch (Throwable $exception) {
            if ($markFailureImmediately) {
                $this->markFailure($order, $exception);
            } else {
                // Queue retries are still active. Keep the state locked as syncing
                // so an admin cannot accidentally start a concurrent manual retry.
                $this->saveSyncState($order, [
                    'flowtrack_sync_status' => self::STATUS_SYNCING,
                    'flowtrack_sync_error' => mb_substr($exception->getMessage(), 0, 5000),
                ]);
            }

            throw $exception;
        }

        $this->saveSyncState($order, [
            'flowtrack_sync_status' => self::STATUS_SYNCED,
            'flowtrack_order_id' => filled(data_get($response, 'data.order_id'))
                ? (int) data_get($response, 'data.order_id')
                : $order->flowtrack_order_id,
            'flowtrack_order_number' => filled(data_get($response, 'data.order_number'))
                ? (string) data_get($response, 'data.order_number')
                : $order->flowtrack_order_number,
            'flowtrack_job_id' => filled(data_get($response, 'data.flow_job_id'))
                ? (string) data_get($response, 'data.flow_job_id')
                : $order->flowtrack_job_id,
            'flowtrack_synced_at' => now(),
            'flowtrack_sync_error' => null,
            'flowtrack_response' => $response,
        ]);

        $order->refresh();

        Log::info('flowtrack.order_sync_succeeded', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'flowtrack_order_id' => $order->flowtrack_order_id,
            'flowtrack_order_number' => $order->flowtrack_order_number,
            'flow_job_id' => $order->flowtrack_job_id,
        ]);

        return $response;
    }

    public function recordPermanentFailure(int $orderId, Throwable $exception): void
    {
        $order = Order::query()->withTrashed()->find($orderId);

        if ($order instanceof Order) {
            $this->markFailure($order, $exception);
            $this->logFailure($order, $exception, 'flowtrack.order_sync_failed_permanently');
            return;
        }

        Log::error('flowtrack.order_sync_failed_permanently', [
            'order_id' => $orderId,
            'exception' => $exception::class,
            'message' => mb_substr($exception->getMessage(), 0, 1500),
        ]);
    }

    private function markFailure(Order $order, Throwable $exception): void
    {
        $this->saveSyncState($order, [
            'flowtrack_sync_status' => self::STATUS_FAILED,
            'flowtrack_last_attempt_at' => $order->flowtrack_last_attempt_at ?: now(),
            'flowtrack_sync_error' => mb_substr($exception->getMessage(), 0, 5000),
        ]);
    }

    /** @param array<string,mixed> $attributes */
    private function saveSyncState(Order $order, array $attributes): void
    {
        if (! $this->syncStateStorageReady()) {
            return;
        }

        $timestamps = $order->timestamps;
        $order->timestamps = false;

        try {
            $order->forceFill($attributes)->saveQuietly();
        } finally {
            $order->timestamps = $timestamps;
        }
    }

    private function incrementAttemptsWithoutTouchingSourceTimestamp(Order $order): void
    {
        if (! $this->syncStateStorageReady()) {
            return;
        }

        $timestamps = $order->timestamps;
        $order->timestamps = false;

        try {
            $order->increment('flowtrack_sync_attempts');
        } finally {
            $order->timestamps = $timestamps;
        }
    }

    private function syncStateStorageReady(): bool
    {
        return $this->storageReady ??= Schema::hasTable('orders') && Schema::hasColumn('orders', 'flowtrack_sync_status');
    }

    private function logFailure(Order $order, Throwable $exception, string $event): void
    {
        Log::error($event, [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'exception' => $exception::class,
            'message' => mb_substr($exception->getMessage(), 0, 1500),
        ]);
    }
}
