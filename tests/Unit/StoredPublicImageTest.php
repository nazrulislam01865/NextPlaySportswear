<?php

namespace Tests\Unit;

use App\Services\Catalog\StoredPublicImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class StoredPublicImageTest extends TestCase
{
    public function test_replacement_is_saved_before_an_old_file_is_retired(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('categories/3/old.jpg', 'old');
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);

        $newPath = StoredPublicImage::replace(
            UploadedFile::fake()->create('new.jpg', 1, 'image/jpeg'),
            'categories/3',
            'categories/3/old.jpg',
        );

        $this->assertNotSame('categories/3/old.jpg', $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('categories/3/old.jpg');
    }

    public function test_old_image_is_not_deleted_until_database_transaction_commits(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('categories/3/old.jpg', 'old');
        DB::shouldReceive('transactionLevel')->once()->andReturn(1);
        $onCommit = null;
        DB::shouldReceive('afterCommit')->once()->andReturnUsing(function ($callback) use (&$onCommit): void {
            $onCommit = $callback;
        });

        $newPath = StoredPublicImage::replace(
            UploadedFile::fake()->create('new.jpg', 1, 'image/jpeg'),
            'categories/3',
            'categories/3/old.jpg',
        );

        Storage::disk('public')->assertExists('categories/3/old.jpg');
        Storage::disk('public')->assertExists($newPath);
        $this->assertIsCallable($onCommit);
        $onCommit();
        Storage::disk('public')->assertMissing('categories/3/old.jpg');
    }

    public function test_failed_upload_preserves_previous_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('categories/3/old.jpg', 'old');
        $failedUpload = \Mockery::mock(UploadedFile::class);
        $failedUpload->shouldReceive('store')->once()->andReturn(false);

        try {
            StoredPublicImage::replace($failedUpload, 'categories/3', 'categories/3/old.jpg');
            $this->fail('An unsuccessful upload must throw an exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('previous image was preserved', $exception->getMessage());
            Storage::disk('public')->assertExists('categories/3/old.jpg');
        }
    }
}
