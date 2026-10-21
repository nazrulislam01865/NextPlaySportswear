<?php

namespace App\Support;

use App\Models\BulkQuoteRequest;

/** Customer-visible task milestones. A verified offer is not a published priced quotation. */
final class FlowTrackQuoteCustomerProgress
{
    public static function published(BulkQuoteRequest $quote): bool
    {
        return (int) $quote->quotation_version > 0 && $quote->quotation_sent_at !== null;
    }

    public static function verifiedOffer(BulkQuoteRequest $quote): bool
    {
        return ! self::published($quote)
            && (int) $quote->flowtrack_offer_file_count > 0
            && filled($quote->flowtrack_offer_manifest_hash)
            && hash_equals((string) $quote->flowtrack_offer_manifest_hash, (string) $quote->flowtrack_offer_ready_hash);
    }

    /** @return array{review:bool,prepared:bool,ready:bool,feedback:bool,review_at:mixed,prepared_at:mixed,ready_at:mixed} */
    public static function milestones(BulkQuoteRequest $quote): array
    {
        $progress = (array) ($quote->flowtrack_task_progress ?? []);
        $published = self::published($quote);
        $ready = $published || self::verifiedOffer($quote);
        $prepared = $ready || filled($progress['prepared_at'] ?? null);
        $review = $prepared || filled($progress['review_at'] ?? null)
            || $quote->status !== BulkQuoteRequest::STATUS_NEW;

        return [
            'review' => $review,
            'prepared' => $prepared,
            'ready' => $ready,
            'feedback' => $ready,
            'review_at' => $progress['review_at'] ?? null,
            'prepared_at' => $progress['prepared_at'] ?? null,
            'ready_at' => $published ? $quote->quotation_sent_at : ($progress['sent_at'] ?? null),
        ];
    }

    public static function title(BulkQuoteRequest $quote): string
    {
        if (in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED], true)) {
            return 'Quotation Confirmed';
        }
        if ($quote->guest_decision === 'changes_requested') return 'Changes Requested';
        if (self::published($quote)) return 'Quotation Ready';
        if (self::verifiedOffer($quote)) return 'Offer File Available';
        $progress = self::milestones($quote);
        if ($progress['prepared']) return 'Quotation Prepared';
        if ($progress['review']) return 'In Review';
        return BulkQuoteRequest::statuses()[$quote->status] ?? 'Under Review';
    }

    public static function description(BulkQuoteRequest $quote): string
    {
        if (in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED], true)) {
            return 'Thank you. Your quotation has been confirmed.';
        }
        if ($quote->guest_decision === 'changes_requested') return 'Our team will review your requested changes.';
        if (self::published($quote)) return 'Your complete quotation is published and ready for review.';
        if (self::verifiedOffer($quote)) return 'Your FlowTracker offer file is available to download. Final prices and payment terms will be published separately.';
        $progress = self::milestones($quote);
        if ($progress['prepared']) return 'Our team has prepared your offer and is completing the review.';
        if ($progress['review']) return 'Our team is reviewing your requirements.';
        return 'Our team is preparing your custom quotation.';
    }
}
