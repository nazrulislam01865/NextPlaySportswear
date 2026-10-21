<?php

namespace App\Services\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Save an uploaded public image before retiring the previously referenced file.
 * File deletion must happen only after the associated database change commits.
 */
final class StoredPublicImage
{
    public static function replace(UploadedFile $file, string $directory, ?string $previousPath): string
    {
        $path = $file->store($directory, 'public');

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            throw new RuntimeException('Unable to save the replacement image. The previous image was preserved.');
        }

        self::retire($previousPath, $path);

        return $path;
    }

    public static function retireDirectory(string $directory): void
    {
        $cleanup = static function () use ($directory): void {
            try {
                Storage::disk('public')->deleteDirectory($directory);
            } catch (\Throwable $exception) {
                report($exception);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($cleanup);
        } else {
            $cleanup();
        }
    }

    public static function retire(?string $previousPath, ?string $replacementPath = null): void
    {
        $path = trim((string) $previousPath);
        if ($path === '' || $path === trim((string) $replacementPath)) {
            return;
        }

        $delete = static function () use ($path): void {
            // Failure to clean up a superseded image must not undo a successful upload.
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $exception) {
                report($exception);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($delete);
        } else {
            $delete();
        }
    }
}
