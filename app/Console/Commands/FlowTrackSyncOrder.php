<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Integrations\FlowTrack\FlowTrackOrderSyncManager;
use Illuminate\Console\Command;
use Throwable;

final class FlowTrackSyncOrder extends Command
{
    protected $signature = 'flowtrack:sync-order {order : NextPlay order number or numeric order ID}';

    protected $description = 'Immediately send one existing NextPlay order to FlowTrack and persist the sync result.';

    public function handle(FlowTrackOrderSyncManager $sync): int
    {
        $value = trim((string) $this->argument('order'));

        $query = Order::query()->withTrashed();
        $order = ctype_digit($value)
            ? $query->whereKey((int) $value)->first()
            : $query->where('order_number', $value)->first();

        if (! $order instanceof Order) {
            $this->error('NextPlay order not found: '.$value);
            return self::FAILURE;
        }

        try {
            $response = $sync->syncNow($order);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $order->refresh();

        $this->info((string) ($response['message'] ?? 'Order accepted by FlowTrack.'));
        $this->line('NextPlay order: '.$order->order_number);
        $this->line('Sync status: '.(string) ($order->flowtrack_sync_status ?: 'unknown'));
        $this->line('FlowTrack order: '.(string) ($order->flowtrack_order_number ?: 'n/a'));
        $this->line('FlowTrack job ID: '.(string) ($order->flowtrack_job_id ?: data_get($response, 'data.flow_job_id', 'n/a')));

        return self::SUCCESS;
    }
}
