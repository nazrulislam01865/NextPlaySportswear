<?php

namespace App\Services\Catalog;

use App\Support\HomepageImageAspectRatios;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class HomepageStagedUploadService
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const MAX_CHUNK_BYTES = 768 * 1024;
    public const MAX_CHUNKS = 32;
    public const TTL_SECONDS = 7200;

    /** @var array<string, string> */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    public function storeChunk(int $userId, string $uploadId, int $index, string $bytes): void
    {
        $this->assertUploadId($uploadId);

        if ($index < 0 || $index >= self::MAX_CHUNKS) {
            throw new RuntimeException('Invalid image upload chunk index.');
        }

        $length = strlen($bytes);
        if ($length < 1 || $length > self::MAX_CHUNK_BYTES) {
            throw new RuntimeException('Each image upload chunk must be between 1 byte and 768 KB.');
        }

        $this->cleanupExpired($userId);
        $path = $this->chunkPath($userId, $uploadId, $index);
        if (! Storage::disk('local')->put($path, $bytes)) {
            throw new RuntimeException('The server could not write the image upload chunk. Check storage permissions.');
        }
    }

    /** @return array{token:string,width:int,height:int,mime_type:string,size:int} */
    public function finalize(int $userId, string $uploadId, int $chunks, int $expectedSize, string $originalName = ''): array
    {
        $this->assertUploadId($uploadId);

        if ($chunks < 1 || $chunks > self::MAX_CHUNKS) {
            throw new RuntimeException('The image upload contains an invalid number of chunks.');
        }
        if ($expectedSize < 1 || $expectedSize > self::MAX_BYTES) {
            throw new RuntimeException('The image must be no larger than 10 MB.');
        }

        $token = hash('sha256', $userId.'|'.$uploadId.'|'.Str::uuid()->toString().'|'.microtime(true));
        $readyPath = $this->readyImagePath($userId, $token);
        Storage::disk('local')->put($readyPath, '');
        $absolutePath = Storage::disk('local')->path($readyPath);
        $output = @fopen($absolutePath, 'wb');
        if (! is_resource($output)) {
            throw new RuntimeException('The server could not prepare temporary storage for the image.');
        }

        $actualSize = 0;
        try {
            for ($index = 0; $index < $chunks; $index++) {
                $chunkPath = $this->chunkPath($userId, $uploadId, $index);
                if (! Storage::disk('local')->exists($chunkPath)) {
                    throw new RuntimeException(sprintf('Image upload chunk %d is missing. Please choose the image again.', $index + 1));
                }

                $input = Storage::disk('local')->readStream($chunkPath);
                if (! is_resource($input)) {
                    throw new RuntimeException('The server could not read a staged image chunk.');
                }
                $copied = stream_copy_to_stream($input, $output);
                fclose($input);
                if ($copied === false) {
                    throw new RuntimeException('The server could not assemble the staged image.');
                }
                $actualSize += (int) $copied;
                if ($actualSize > self::MAX_BYTES) {
                    throw new RuntimeException('The image must be no larger than 10 MB.');
                }
            }
        } catch (\Throwable $e) {
            fclose($output);
            Storage::disk('local')->delete($readyPath);
            Storage::disk('local')->deleteDirectory($this->uploadDirectory($userId, $uploadId));
            throw $e;
        }
        fclose($output);

        Storage::disk('local')->deleteDirectory($this->uploadDirectory($userId, $uploadId));

        if ($actualSize !== $expectedSize) {
            Storage::disk('local')->delete($readyPath);
            throw new RuntimeException('The image upload was incomplete. Please choose the image again.');
        }

        $imageInfo = @getimagesize($absolutePath);
        if (! is_array($imageInfo) || ! isset($imageInfo[0], $imageInfo[1])) {
            Storage::disk('local')->delete($readyPath);
            throw new RuntimeException('The selected file is not a readable image.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($absolutePath);
        if (! isset(self::EXTENSIONS[$mime])) {
            Storage::disk('local')->delete($readyPath);
            throw new RuntimeException('Homepage images must be JPG, PNG, WebP, or AVIF files.');
        }

        $metadata = [
            'created_at' => time(),
            'original_name' => mb_substr(trim($originalName), 0, 255),
            'mime_type' => $mime,
            'extension' => self::EXTENSIONS[$mime],
            'size' => $actualSize,
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
        ];

        if (! Storage::disk('local')->put($this->readyMetadataPath($userId, $token), json_encode($metadata, JSON_THROW_ON_ERROR))) {
            Storage::disk('local')->delete($readyPath);
            throw new RuntimeException('The server could not save temporary image metadata.');
        }

        return [
            'token' => $token,
            'width' => $metadata['width'],
            'height' => $metadata['height'],
            'mime_type' => $mime,
            'size' => $actualSize,
        ];
    }

    /** @param array{ratio:string,width:int,height:int,label:string,tolerance?:float}|null $definition */
    public function validationError(int $userId, string $token, ?array $definition = null): ?string
    {
        $metadata = $this->metadata($userId, $token);
        if ($metadata === null) {
            return 'The staged image has expired or is invalid. Please choose the image again.';
        }

        if ($definition !== null && ! HomepageImageAspectRatios::matches(
            (int) ($metadata['width'] ?? 0),
            (int) ($metadata['height'] ?? 0),
            $definition,
        )) {
            $percent = (int) round(((float) ($definition['tolerance'] ?? HomepageImageAspectRatios::DEFAULT_TOLERANCE)) * 100);

            return sprintf(
                'The %s image should use an approximately %s aspect ratio (±%d%% accepted). Any suitable resolution is accepted.',
                (string) ($definition['label'] ?? 'uploaded'),
                (string) $definition['ratio'],
                $percent,
            );
        }

        return null;
    }

    public function consumeToPublic(int $userId, string $token, string $destinationDirectory): string
    {
        $metadata = $this->metadata($userId, $token);
        if ($metadata === null) {
            throw new RuntimeException('The staged image has expired or is invalid. Please choose the image again.');
        }

        $sourcePath = $this->readyImagePath($userId, $token);
        $source = Storage::disk('local')->readStream($sourcePath);
        if (! is_resource($source)) {
            throw new RuntimeException('The staged image could not be read. Please choose the image again.');
        }

        $extension = (string) ($metadata['extension'] ?? 'jpg');
        $destinationDirectory = trim($destinationDirectory, '/');
        $destinationPath = $destinationDirectory.'/'.Str::uuid()->toString().'.'.$extension;

        $written = Storage::disk('public')->writeStream($destinationPath, $source);
        fclose($source);
        if (! $written) {
            throw new RuntimeException('The server could not save the homepage image. Check public storage permissions.');
        }

        Storage::disk('local')->delete([$sourcePath, $this->readyMetadataPath($userId, $token)]);

        return $destinationPath;
    }

    /** @return array<string, mixed>|null */
    public function metadata(int $userId, string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $imagePath = $this->readyImagePath($userId, $token);
        $metadataPath = $this->readyMetadataPath($userId, $token);
        if (! Storage::disk('local')->exists($imagePath) || ! Storage::disk('local')->exists($metadataPath)) {
            return null;
        }

        $decoded = json_decode((string) Storage::disk('local')->get($metadataPath), true);
        if (! is_array($decoded)) {
            return null;
        }

        if ((int) ($decoded['created_at'] ?? 0) < time() - self::TTL_SECONDS) {
            Storage::disk('local')->delete([$imagePath, $metadataPath]);
            return null;
        }

        return $decoded;
    }

    public function cleanupExpired(int $userId): void
    {
        $disk = Storage::disk('local');
        $prefix = "homepage-upload-staging/{$userId}/ready";
        foreach ($disk->files($prefix) as $path) {
            if (! str_ends_with($path, '.json')) {
                continue;
            }
            $decoded = json_decode((string) $disk->get($path), true);
            if (! is_array($decoded) || (int) ($decoded['created_at'] ?? 0) < time() - self::TTL_SECONDS) {
                $token = basename($path, '.json');
                $disk->delete([$path, $this->readyImagePath($userId, $token)]);
            }
        }

        $uploadsPrefix = "homepage-upload-staging/{$userId}/uploads";
        foreach ($disk->directories($uploadsPrefix) as $directory) {
            $files = $disk->files($directory);
            if ($files === []) {
                $disk->deleteDirectory($directory);
                continue;
            }
            $lastModified = max(array_map(fn (string $path): int => (int) $disk->lastModified($path), $files));
            if ($lastModified < time() - self::TTL_SECONDS) {
                $disk->deleteDirectory($directory);
            }
        }
    }

    private function assertUploadId(string $uploadId): void
    {
        if (! preg_match('/^[A-Za-z0-9_-]{8,100}$/', $uploadId)) {
            throw new RuntimeException('Invalid image upload identifier.');
        }
    }

    private function uploadDirectory(int $userId, string $uploadId): string
    {
        return "homepage-upload-staging/{$userId}/uploads/{$uploadId}";
    }

    private function chunkPath(int $userId, string $uploadId, int $index): string
    {
        return $this->uploadDirectory($userId, $uploadId).'/'.str_pad((string) $index, 4, '0', STR_PAD_LEFT).'.part';
    }

    private function readyImagePath(int $userId, string $token): string
    {
        return "homepage-upload-staging/{$userId}/ready/{$token}.bin";
    }

    private function readyMetadataPath(int $userId, string $token): string
    {
        return "homepage-upload-staging/{$userId}/ready/{$token}.json";
    }
}
