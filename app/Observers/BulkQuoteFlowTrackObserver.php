<?php

namespace App\Observers;

use App\Models\BulkQuoteRequest;
use App\Services\Integrations\FlowTrack\FlowTrackInquirySyncManager;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/** Publish meaningful customer/quote changes, never synchronization bookkeeping. */
final class BulkQuoteFlowTrackObserver implements ShouldHandleEventsAfterCommit
{
    private const FIELDS = [
        'full_name', 'organization', 'email', 'phone', 'customer_account', 'user_id',
        'product_type', 'estimated_quantity', 'sizes_needed', 'budget_range',
        'artwork_details', 'customization_types', 'shipping_address', 'country',
        'state_province', 'postal_code', 'preferred_shipping_method', 'needed_by',
        'event_date', 'attachment', 'additional_notes', 'status', 'guest_decision',
    ];

    public function updated(BulkQuoteRequest $quote): void
    {
        // Imported FlowTracker records are updated without model events to avoid echo.
        if ($quote->wasChanged(self::FIELDS)) {
            app(FlowTrackInquirySyncManager::class)->dispatch($quote);
        }
    }
}
