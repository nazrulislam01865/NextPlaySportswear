<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\AboutPageRequest;
use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AboutPageMediaService
{
    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $validated
     * @return array{payload:array<string,mixed>,new_paths:array<int,string>,delete_after_commit:array<int,string>}
     */
    public function prepare(AboutPageRequest $request, array $current, array $validated): array
    {
        $mutation = [
            'payload' => $validated,
            'new_paths' => [],
            'delete_after_commit' => [],
        ];

        try {
            foreach ($this->slots() as $slot) {
                $currentPath = data_get($current, $slot['data_path']);
                $currentPath = is_string($currentPath) && trim($currentPath) !== ''
                    ? PublicMedia::normalizePath($currentPath)
                    : null;

                data_set($mutation['payload'], $slot['data_path'], $currentPath);

                $file = $request->file($slot['file']);
                if ($file instanceof UploadedFile) {
                    $newPath = $file->store($slot['directory'], 'public');
                    if (! is_string($newPath) || $newPath === '') {
                        throw new RuntimeException('Unable to store About page media.');
                    }

                    $newPath = PublicMedia::normalizePath($newPath);
                    data_set($mutation['payload'], $slot['data_path'], $newPath);
                    $mutation['new_paths'][] = $newPath;

                    if ($currentPath !== null && $currentPath !== $newPath && $this->isAboutOwnedPath($currentPath)) {
                        $mutation['delete_after_commit'][] = $currentPath;
                    }

                    continue;
                }

                if ($request->boolean($slot['remove'])) {
                    data_set($mutation['payload'], $slot['data_path'], null);

                    if ($currentPath !== null && $this->isAboutOwnedPath($currentPath)) {
                        $mutation['delete_after_commit'][] = $currentPath;
                    }
                }
            }

        } catch (Throwable $exception) {
            $this->rollback($mutation);
            throw $exception;
        }

        $mutation['new_paths'] = array_values(array_unique($mutation['new_paths']));
        $mutation['delete_after_commit'] = array_values(array_unique($mutation['delete_after_commit']));

        return $mutation;
    }

    /** @param array{payload?:array<string,mixed>,new_paths?:array<int,string>,delete_after_commit?:array<int,string>} $mutation */
    public function commitCleanup(array $mutation): void
    {
        $this->deleteOwnedPaths($mutation['delete_after_commit'] ?? []);
    }

    /** @param array{payload?:array<string,mixed>,new_paths?:array<int,string>,delete_after_commit?:array<int,string>} $mutation */
    public function rollback(array $mutation): void
    {
        $this->deleteOwnedPaths($mutation['new_paths'] ?? []);
    }

    public function isAboutOwnedPath(string $path): bool
    {
        $normalized = PublicMedia::normalizePath($path);
        if (! str_starts_with($normalized, 'about-page/')) {
            return false;
        }

        if (str_contains($normalized, "\0")) {
            return false;
        }

        $segments = explode('/', $normalized);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return count($segments) >= 3;
    }

    /** @return array<int, array{file:string,remove:string,data_path:string,directory:string}> */
    private function slots(): array
    {
        $slots = [[
            'file' => 'introduction_image',
            'remove' => 'remove_introduction_image',
            'data_path' => 'introduction.image_path',
            'directory' => 'about-page/introduction',
        ]];

        for ($i = 0; $i < 3; $i++) {
            $slots[] = [
                'file' => 'service_icon_'.$i,
                'remove' => 'remove_service_icon_'.$i,
                'data_path' => 'what_we_do.cards.'.$i.'.icon_path',
                'directory' => 'about-page/what-we-do/icons',
            ];
        }

        for ($i = 0; $i < 4; $i++) {
            $slots[] = [
                'file' => 'process_icon_'.$i,
                'remove' => 'remove_process_icon_'.$i,
                'data_path' => 'how_we_work.steps.'.$i.'.icon_path',
                'directory' => 'about-page/how-we-work/icons',
            ];
        }

        for ($i = 0; $i < 4; $i++) {
            $slots[] = [
                'file' => 'gallery_image_'.$i,
                'remove' => 'remove_gallery_image_'.$i,
                'data_path' => 'gallery.items.'.$i.'.image_path',
                'directory' => 'about-page/gallery',
            ];
        }

        $slots[] = [
            'file' => 'help_icon',
            'remove' => 'remove_help_icon',
            'data_path' => 'help.icon_path',
            'directory' => 'about-page/help/icons',
        ];

        return $slots;
    }

    /** @param array<int, mixed> $paths */
    private function deleteOwnedPaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (! is_string($path) || ! $this->isAboutOwnedPath($path)) {
                continue;
            }

            Storage::disk('public')->delete(PublicMedia::normalizePath($path));
        }
    }
}
