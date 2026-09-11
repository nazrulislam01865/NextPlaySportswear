<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

final class FlowTrackInspectOrder extends Command
{
    protected $signature = 'flowtrack:inspect-order {order : NextPlay order number or database ID}';

    protected $description = 'Inspect the immutable product snapshots NextPlay will send to FlowTrack.';

    public function handle(): int
    {
        $value = trim((string) $this->argument('order'));
        $order = Order::query()
            ->withTrashed()
            ->with('items')
            ->where(function ($query) use ($value): void {
                $query->where('order_number', $value);
                if (ctype_digit($value)) {
                    $query->orWhereKey((int) $value);
                }
            })
            ->first();

        if (! $order) {
            $this->error('No NextPlay order matched '.$value.'.');
            return self::FAILURE;
        }

        $this->info('NextPlay order source snapshot.');
        $this->table(['Field', 'Value'], [
            ['Order', $order->order_number],
            ['Order ID', $order->id],
            ['Items', $order->items->count()],
            ['Items with product snapshot', $order->items->filter(fn ($item) => ! empty($item->product_snapshot))->count()],
            ['Items with resolved customization', $order->items->filter(fn ($item) => ! empty($item->resolved_customization))->count()],
        ]);

        $this->newLine();
        $this->table(
            ['Item', 'Product', 'SKU', 'Snapshot', 'Reason', 'Options', 'Sizes', 'Roster', 'Artwork'],
            $order->items->map(function ($item): array {
                $snapshot = (array) ($item->product_snapshot ?? []);
                $resolved = (array) ($item->resolved_customization ?? []);

                return [
                    $item->id,
                    $item->product_name,
                    $item->sku ?: '-',
                    ! empty($snapshot) ? 'yes (v'.($item->product_snapshot_schema_version ?: '?').')' : 'no',
                    data_get($snapshot, 'capture_reason', '-'),
                    count((array) ($resolved['options'] ?? [])),
                    count((array) ($resolved['sizes'] ?? [])),
                    count((array) data_get($resolved, 'roster.rows', [])),
                    count($item->artworkFiles()),
                ];
            })->all(),
        );

        return self::SUCCESS;
    }
}
