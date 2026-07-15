<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    /**
     * Serve application-owned public uploads without relying on the public/storage symlink.
     *
     * This keeps uploaded product/category/homepage images visible on cloud deployments
     * where public/storage may be a copied directory instead of a real symlink.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = $this->normalizePath($path);

        if ($path === '' || $this->containsUnsafeSegment($path)) {
            abort(404);
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $absolutePath = $disk->path($path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $mimeType = $this->safeMimeType($path, $extension);
        if (! str_starts_with($mimeType, 'image/')) {
            abort(404);
        }

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim(rawurldecode($path)));
        $path = preg_replace('#/+#', '/', $path) ?: '';

        return ltrim($path, '/');
    }

    private function containsUnsafeSegment(string $path): bool
    {
        return collect(explode('/', $path))->contains(
            static fn (string $segment): bool => $segment === '' || $segment === '.' || $segment === '..'
        );
    }

    private function safeMimeType(string $path, string $extension): string
    {
        $mimeType = Storage::disk('public')->mimeType($path) ?: '';

        if (str_starts_with($mimeType, 'image/')) {
            return $mimeType;
        }

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            default => 'application/octet-stream',
        };
    }
}
