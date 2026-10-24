<?php

namespace App\Services\Catalog;

use App\Support\PublicMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class MasterMethodMediaService
{
    private const ALLOWED_DIRECTORIES = [
        'master-data/production-methods',
        'master-data/shipping-methods',
    ];

    public function persist(Model $method, Request $request, string $directory): void
    {
        $directory = $this->ownedDirectory($directory);

        DB::transaction(function () use ($method, $request, $directory): void {
            $currentPath = filled($method->getAttribute('image_path'))
                ? PublicMedia::normalizePath((string) $method->getAttribute('image_path'))
                : null;
            $imagePath = $currentPath;
            $imageUrl = filled($method->getAttribute('image_url'))
                ? trim((string) $method->getAttribute('image_url'))
                : null;

            if ($request->hasFile('image_file')) {
                $imagePath = StoredPublicImage::replace($request->file('image_file'), $directory, $currentPath);
                if ($directory === 'master-data/shipping-methods') {
                    $cleanedPath = ProductDetailIconNormalizer::normalize($imagePath);
                    if ($cleanedPath !== $imagePath) {
                        Storage::disk('public')->delete($imagePath);
                        $imagePath = $cleanedPath;
                    }
                }
                $imageUrl = null;
            } elseif ($request->boolean('remove_image')) {
                $this->deleteOwned($currentPath, $directory);
                $imagePath = null;
                $imageUrl = null;
            } else {
                $submittedUrl = trim((string) $request->input('image_url', ''));
                if ($submittedUrl !== '' && $submittedUrl !== $imageUrl) {
                    $this->deleteOwned($currentPath, $directory);
                    $imagePath = null;
                    $imageUrl = $submittedUrl;
                }
            }

            $method->forceFill(['image_path' => $imagePath, 'image_url' => $imageUrl])->save();
        });
    }

    public function deleteOwned(?string $path, string $directory): void
    {
        if (! filled($path)) {
            return;
        }

        $directory = $this->ownedDirectory($directory);
        $normalized = PublicMedia::normalizePath((string) $path);
        if (! str_starts_with($normalized, $directory.'/')) {
            return;
        }

        StoredPublicImage::retire($normalized);
    }

    private function ownedDirectory(string $directory): string
    {
        $directory = trim(PublicMedia::normalizePath($directory), '/');
        abort_unless(in_array($directory, self::ALLOWED_DIRECTORIES, true), 500, 'Unsupported master media directory.');

        return $directory;
    }
}
