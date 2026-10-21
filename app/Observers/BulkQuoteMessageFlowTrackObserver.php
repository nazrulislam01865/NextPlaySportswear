<?php

namespace App\Observers;

use App\Models\BulkQuoteMessage;
use App\Models\BulkQuoteRequest;
use App\Services\Integrations\FlowTrack\FlowTrackInquirySyncManager;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class BulkQuoteMessageFlowTrackObserver implements ShouldHandleEventsAfterCommit
{
    public function created(BulkQuoteMessage $message): void
    {
        if ($message->sender !== 'guest') return;
        $quote = BulkQuoteRequest::query()->find($message->bulk_quote_request_id);
        if ($quote) app(FlowTrackInquirySyncManager::class)->dispatch($quote);
    }
}
