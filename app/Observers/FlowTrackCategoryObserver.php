<?php

namespace App\Observers;

use App\Jobs\Integrations\NotifyFlowTrackCatalogChange;
use App\Models\Category;

/** Single-category events, never a full tree refresh on ordinary edits. */
final class FlowTrackCategoryObserver
{
    public function created(Category $category): void { $this->notify($category); }
    public function updated(Category $category): void
    {
        if ($category->wasChanged(['name', 'parent_id', 'sort_order', 'status', 'is_active', 'deleted_at'])) $this->notify($category);
    }
    public function deleted(Category $category): void { $this->notify($category); }
    public function restored(Category $category): void { $this->notify($category); }

    private function notify(Category $category): void
    {
        if (! config('flowtrack.catalog_events_enabled', false)) return;
        NotifyFlowTrackCatalogChange::dispatch('category', (int) $category->id)
            ->onConnection((string) config('flowtrack.queue.connection', 'database'))
            ->onQueue((string) config('flowtrack.queue.name', 'integrations'))->afterCommit();
    }
}
