<?php

namespace Tests\Feature;

use App\Services\Email\ForwardingSettings;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ConfirmationForwardingSettingsTest extends TestCase
{
    public function test_defaults_are_persisted_on_private_local_disk_without_database(): void
    {
        Storage::fake('local');
        $settings = app(ForwardingSettings::class);

        $settings->save('production@example.com, manager@example.com', 'Please confirm the order.');

        Storage::disk('local')->assertExists('email/forwarding-settings.json');
        $this->assertSame([
            'recipients' => 'production@example.com, manager@example.com',
            'message' => 'Please confirm the order.',
        ], $settings->get());
        $this->assertCount(2, $settings->recipients($settings->get()['recipients']));
    }

    public function test_invalid_forwarding_recipient_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid recipient address');
        app(ForwardingSettings::class)->recipients('not-an-email');
    }
}
