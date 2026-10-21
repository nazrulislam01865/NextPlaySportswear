<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkQuoteFile;
use App\Models\BulkQuoteRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Private published quote file, accessible only via the shared server token. */
final class FlowTrackPublishedQuotationFileController extends Controller
{
    public function __invoke(BulkQuoteRequest $bulkQuote, BulkQuoteFile $file): StreamedResponse
    {
        abort_unless((int) $file->bulk_quote_request_id === (int) $bulkQuote->id
            && $file->category === 'quotation'
            && (int) $file->version === (int) $bulkQuote->quotation_version
            && $bulkQuote->quotation_sent_at !== null, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return Storage::disk('local')->download($file->path, basename($file->original_name), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
