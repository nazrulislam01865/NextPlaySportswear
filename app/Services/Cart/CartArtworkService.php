<?php

namespace App\Services\Cart;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class CartArtworkService
{
    public static function retentionToken(string $path): string
    {
        return hash_hmac('sha256', $path, (string) config('app.key', 'nextplay-cart-artwork'));
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<int, array<string, mixed>>  $existingArtwork
     * @return array{files: array<int, array<string, mixed>>, new_paths: array<int, string>}
     */
    public function prepare(Request $request, array $product, array $existingArtwork = []): array
    {
        $settings = $product['artwork_upload'] ?? ['enabled' => false];
        $enabled = (bool) ($settings['enabled'] ?? false);
        $maximumFiles = max(1, min(12, (int) ($settings['max_files'] ?? 5)));
        $maximumSizeMb = max(1, min(25, (int) ($settings['max_file_size_mb'] ?? 15)));
        $acceptedTypes = collect($settings['accepted_types'] ?? ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'])
            ->map(fn ($type) => strtolower(ltrim(trim((string) $type), '.')))
            ->filter(fn ($type) => in_array($type, ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp'], true))
            ->unique()
            ->values();

        $existingByPath = collect($existingArtwork)
            ->filter(fn ($file): bool => is_array($file) && filled($file['path'] ?? null))
            ->map(fn (array $file): array => [
                'path' => (string) $file['path'],
                'original_name' => mb_substr((string) ($file['original_name'] ?? 'Artwork file'), 0, 255),
                'size' => max(0, (int) ($file['size'] ?? 0)),
                'mime_type' => mb_substr((string) ($file['mime_type'] ?? 'application/octet-stream'), 0, 120),
            ])
            ->keyBy('path');

        if (! $enabled) {
            $retainedArtwork = $existingByPath->values();
        } elseif ($request->has('retained_artwork_tokens')) {
            $existingByToken = $existingByPath->mapWithKeys(
                fn (array $file): array => [self::retentionToken((string) $file['path']) => $file]
            );
            $retainedArtwork = collect((array) $request->input('retained_artwork_tokens', []))
                ->map(fn ($token) => $existingByToken->get((string) $token))
                ->filter()
                ->unique('path')
                ->values();
        } elseif ($request->has('retained_artwork_json')) {
            $retainedPaths = json_decode((string) $request->input('retained_artwork_json', '[]'), true);
            $retainedArtwork = collect(is_array($retainedPaths) ? $retainedPaths : [])
                ->map(fn ($path) => $existingByPath->get((string) $path))
                ->filter()
                ->unique('path')
                ->values();
        } else {
            $retainedArtwork = $existingByPath->values();
        }

        $uploads = collect((array) $request->file('artwork_files', []));
        if ($request->hasFile('artwork_file')) {
            $uploads->push($request->file('artwork_file'));
        }
        $uploads = $uploads->filter()->values();

        if (! $enabled && $uploads->isNotEmpty()) {
            throw ValidationException::withMessages([
                'artwork_files' => 'Custom artwork uploads are not enabled for this product.',
            ]);
        }

        if ($retainedArtwork->count() + $uploads->count() > $maximumFiles) {
            throw ValidationException::withMessages([
                'artwork_files' => "You may keep or upload a maximum of {$maximumFiles} artwork files for this product.",
            ]);
        }

        foreach ($uploads as $file) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            if (! $acceptedTypes->contains($extension)) {
                throw ValidationException::withMessages([
                    'artwork_files' => 'One or more artwork files use an unsupported file type.',
                ]);
            }
            if ((int) $file->getSize() > ($maximumSizeMb * 1024 * 1024)) {
                throw ValidationException::withMessages([
                    'artwork_files' => "Each artwork file must be no larger than {$maximumSizeMb} MB.",
                ]);
            }
        }

        if ($enabled && ($settings['required'] ?? false) && $retainedArtwork->isEmpty() && $uploads->isEmpty()) {
            throw ValidationException::withMessages([
                'artwork_files' => 'Upload or retain at least one custom artwork file for this product.',
            ]);
        }

        $storedArtwork = $uploads->map(function ($file) use ($request): array {
            $path = $file->store(
                'customer-artwork/'.hash('sha256', $request->session()->getId()),
                'local'
            );

            return [
                'path' => $path,
                'original_name' => mb_substr(basename((string) $file->getClientOriginalName()), 0, 255),
                'size' => (int) $file->getSize(),
                'mime_type' => mb_substr((string) ($file->getMimeType() ?: 'application/octet-stream'), 0, 120),
            ];
        })->values();

        $files = $retainedArtwork->concat($storedArtwork)->values()->all();

        return [
            'files' => $files,
            'new_paths' => $storedArtwork->pluck('path')->filter()->values()->all(),
        ];
    }
}
