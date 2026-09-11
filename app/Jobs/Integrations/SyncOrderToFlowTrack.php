<?php

namespace App\Jobs\Integrations;

use App\Models\Order;
use App\Services\Integrations\FlowTrack\FlowTrackOrderSyncManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncOrderToFlowTrack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public function __construct(public readonly int $orderId)
    {
        $this->tries = max(1, (int) config('flowtrack.queue.tries', 5));
        $this->timeout = max(10, (int) config('flowtrack.queue.timeout', 30));
    }

    public function handle(FlowTrackOrderSyncManager $sync): void
    {
        $order = Order::query()->withTrashed()->find($this->orderId);

        if (! $order instanceof Order) {
            Log::warning('flowtrack.order_sync_skipped_missing_order', ['order_id' => $this->orderId]);
            return;
        }

        $sync->syncNow($order, false);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return (array) config('flowtrack.queue.backoff', [10, 60, 300, 900, 1800]);
    }

    public function failed(Throwable $exception): void
    {
        app(FlowTrackOrderSyncManager::class)->recordPermanentFailure($this->orderId, $exception);
    }
}
