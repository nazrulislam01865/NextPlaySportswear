<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkQuoteRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FlowTrackBulkQuoteAttachmentController extends Controller
{
    public function show(BulkQuoteRequest $bulkQuote): BinaryFileResponse
    {
        $attachment = is_array($bulkQuote->attachment) ? $bulkQuote->attachment : [];
        $path = trim((string) ($attachment['path'] ?? ''));

        abort_if($path === '' || ! Storage::disk('public')->exists($path), 404);

        $name = trim(basename((string) ($attachment['original_name'] ?? 'bulk-quote-attachment')));
        if ($name === '') {
            $name = 'bulk-quote-attachment';
        }

        $response = response()->download(
            Storage::disk('public')->path($path),
            $name,
            ['Cache-Control' => 'private, no-store, max-age=0'],
        );

        $mime = trim((string) ($attachment['mime_type'] ?? ''));
        if ($mime !== '') {
            $response->headers->set('Content-Type', $mime);
        }

        return $response;
    }
}
