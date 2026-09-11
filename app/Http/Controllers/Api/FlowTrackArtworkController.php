<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams one immutable ordered artwork file to authenticated FlowTrack.
 * The caller supplies only the order-item id and file index; storage paths are
 * always resolved from the persisted order snapshot and never from URL input.
 */
final class FlowTrackArtworkController extends Controller
{
    public function show(OrderItem $orderItem, int $index): StreamedResponse|Response
    {
        abort_if($index < 0, 404);

        $file = collect($orderItem->artworkFiles())->values()->get($index);
        abort_unless(is_array($file), 404);

        $path = trim((string) ($file['path'] ?? ''));
        abort_if($path === '' || ! str_starts_with($path, 'customer-artwork/'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404, 'Artwork file is no longer available in NextPlay storage.');

        $absolutePath = $disk->path($path);
        $checksum = is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null;
        $name = basename((string) ($file['original_name'] ?? basename($path)));
        $mime = (string) ($file['mime_type'] ?? 'application/octet-stream');

        return $disk->download($path, $name, array_filter([
            'Content-Type' => $mime,
            'X-NextPlay-Order-Item' => (string) $orderItem->id,
            'X-NextPlay-Artwork-Index' => (string) $index,
            'X-Content-SHA256' => $checksum,
            'Cache-Control' => 'private, no-store',
        ]));
    }
}
