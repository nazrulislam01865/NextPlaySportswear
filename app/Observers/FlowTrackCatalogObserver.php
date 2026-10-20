<?php

namespace App\Observers;

use App\Jobs\Integrations\NotifyFlowTrackCatalogChange;
use App\Models\Product;
use App\Models\User;

final class FlowTrackCatalogObserver
{
    public function created(object $model): void { $this->notify($model); }
    public function updated(object $model): void
    {
        $fields = $model instanceof Product
            ? ['name', 'sku', 'status', 'is_active', 'category_id', 'base_price', 'currency', 'deleted_at']
            : ['name', 'phone', 'company_name', 'email', 'role', 'is_active'];
        if ($model->wasChanged($fields)) $this->notify($model);
    }
    public function deleted(object $model): void { $this->notify($model); }
    public function restored(object $model): void { $this->notify($model); }

    private function notify(object $model): void
    {
        if (! config('flowtrack.catalog_events_enabled', false)) return;
        if ($model instanceof User && $model->role !== 'customer') return;
        $type = $model instanceof Product ? 'product' : 'customer';
        NotifyFlowTrackCatalogChange::dispatch($type, (int) $model->id)
            ->onConnection((string) config('flowtrack.queue.connection', 'database'))
            ->onQueue((string) config('flowtrack.queue.name', 'integrations'))->afterCommit();
    }
}
