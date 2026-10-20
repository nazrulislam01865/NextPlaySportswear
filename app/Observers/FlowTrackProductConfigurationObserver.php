<?php

namespace App\Observers;

use App\Jobs\Integrations\NotifyFlowTrackCatalogChange;
use App\Models\ProductFabricPriceTier;
use App\Models\ProductOptionValue;
use App\Models\ProductSize;

/** Updates invalidate FlowTracker's short-lived details cache via the source product. */
final class FlowTrackProductConfigurationObserver
{
    public function created(object $record): void { $this->notify($record); }
    public function updated(object $record): void { $this->notify($record); }
    public function deleted(object $record): void { $this->notify($record); }
    public function restored(object $record): void { $this->notify($record); }

    private function notify(object $record): void
    {
        if (! config('flowtrack.catalog_events_enabled', false)) return;
        $sourceId = match (true) {
            $record instanceof ProductOptionValue => $record->group?->product_id,
            $record instanceof ProductSize => $record->group?->product_id,
            $record instanceof ProductFabricPriceTier => $record->table?->product_id,
            default => $record->product_id ?? null,
        };
        if (! $sourceId) return;
        NotifyFlowTrackCatalogChange::dispatch('product', (int) $sourceId)
            ->onConnection((string) config('flowtrack.queue.connection', 'database'))
            ->onQueue((string) config('flowtrack.queue.name', 'integrations'))->afterCommit();
    }
}
