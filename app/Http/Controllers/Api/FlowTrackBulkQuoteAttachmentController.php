<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkQuoteRequest;
use App\Models\BulkQuoteFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FlowTrackBulkQuoteAttachmentController extends Controller
{
    public function clientFile(BulkQuoteRequest $bulkQuote, BulkQuoteFile $file): BinaryFileResponse
    {
        abort_unless((int) $file->bulk_quote_request_id === (int) $bulkQuote->id
            && $file->category === 'feedback' && $file->bulk_quote_message_id !== null, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return response()->download(Storage::disk('local')->path($file->path),
            basename($file->original_name), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function show(BulkQuoteRequest $bulkQuote): BinaryFileResponse
    {
        $attachment = is_array($bulkQuote->attachment) ? $bulkQuote->attachment : [];
        $path = trim((string) ($attachment['path'] ?? ''));

        $disk = ($attachment['disk'] ?? '') === 'local' ? 'local' : 'public';
        abort_if($path === '' || ! Storage::disk($disk)->exists($path), 404);

        $name = trim(basename((string) ($attachment['original_name'] ?? 'bulk-quote-attachment')));
        if ($name === '') {
            $name = 'bulk-quote-attachment';
        }

        $response = response()->download(
            Storage::disk($disk)->path($path),
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
