<?php

namespace Tests\Feature;

use App\Services\Cart\PersonalizationUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SharedPersonalizationUploadTest extends TestCase
{
    private function product(): array
    {
        return ['roster' => [
            'shared_entry_enabled' => true,
            'fields' => [['key' => 'sample', 'type' => 'image', 'enabled' => true, 'required' => true]],
        ]];
    }

    private function requestFor(array $configuration, ?UploadedFile $file = null): Request
    {
        $files = $file ? ['personalization_shared_files' => ['sample' => $file]] : [];
        $request = Request::create('/cart/items', 'POST', [
            'configuration_json' => json_encode($configuration, JSON_THROW_ON_ERROR),
        ], [], $files);
        $request->setLaravelSession($this->app['session.store']);

        return $request;
    }

    public function test_one_shared_image_is_stored_once_for_a_large_order(): void
    {
        Storage::fake('local');
        $rows = array_fill(0, 50, ['values' => ['sample' => 'jersey.png']]);
        $result = app(PersonalizationUploadService::class)->prepare(
            $this->requestFor(['roster_same_for_all' => true, 'roster' => $rows], UploadedFile::fake()->createWithContent('jersey.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO/a9b8AAAAASUVORK5CYII='))),
            $this->product()
        );

        $this->assertCount(1, $result['files']);
        $this->assertSame(-1, $result['files'][0]['row']);
        $this->assertCount(50, json_decode($result['configuration_json'], true)['roster']);
        $this->assertSame('jersey.png', json_decode($result['configuration_json'], true)['roster'][49]['values']['sample']);
        Storage::disk('local')->assertExists($result['files'][0]['path']);
    }

    public function test_shared_upload_is_rejected_without_enabled_shared_mode(): void
    {
        Storage::fake('local');
        $this->expectException(ValidationException::class);
        app(PersonalizationUploadService::class)->prepare(
            $this->requestFor(['roster_same_for_all' => false, 'roster' => [['values' => ['sample' => 'jersey.png']]]], UploadedFile::fake()->createWithContent('jersey.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO/a9b8AAAAASUVORK5CYII='))),
            $this->product()
        );
    }

    public function test_existing_shared_file_is_retained_while_editing(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('customer-personalization/old/shared.png', 'image-content');
        $existing = [[
            'row' => -1, 'key' => 'sample', 'path' => 'customer-personalization/old/shared.png',
            'original_name' => 'shared.png', 'mime_type' => 'image/png', 'size' => 13, 'type' => 'image',
        ]];
        $result = app(PersonalizationUploadService::class)->prepare(
            $this->requestFor(['roster_same_for_all' => true, 'roster' => [
                ['values' => ['sample' => 'shared.png']], ['values' => ['sample' => 'shared.png']],
            ]]),
            $this->product(), $existing
        );
        $this->assertSame($existing, $result['files']);
        $this->assertSame([], $result['new_paths']);
    }
}
