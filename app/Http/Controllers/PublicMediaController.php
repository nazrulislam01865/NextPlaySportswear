<?php

namespace App\Http\Controllers;

use App\Support\PublicMedia;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicMediaController extends Controller
{
    public function __invoke(string $path): StreamedResponse|Response
    {
        $path = PublicMedia::normalizePath(rawurldecode($path));

        abort_if(
            $path === ''
            || str_contains($path, "\0")
            || str_contains($path, '\\')
            || preg_match('#(^|/)\.\.(/|$)#', $path) === 1,
            404
        );

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mime = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
        abort_unless(str_starts_with(strtolower($mime), 'image/'), 404);

        $stream = $disk->readStream($path);
        abort_if($stream === false, 404);

        $size = $disk->size($path);
        $filename = basename($path);

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) $size,
            'Content-Disposition' => "inline; filename*=UTF-8''".rawurlencode($filename),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
