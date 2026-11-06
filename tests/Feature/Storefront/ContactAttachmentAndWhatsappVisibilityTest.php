<?php

namespace Tests\Feature\Storefront;

use App\Models\ContactMessage;
use App\Models\StorefrontPageSetting;
use App\Models\User;
use App\Services\Storefront\ContactPageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactAttachmentAndWhatsappVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep APP_ENV=testing so Laravel's HTTP test harness bypasses CSRF
        // normally; changing it to "local" makes POST/PUT requests fail with 419.
        // Exercise the real CAPTCHA rule without contacting Google.
        config([
            'transactional_email.enabled' => false,
            'services.recaptcha.site_key' => 'test-site-key',
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'hostname' => parse_url(route('contact.store'), PHP_URL_HOST),
            ], 200),
        ]);
    }

    private function contactPayload(): array
    {
        return [
            'name' => 'Jordan Smith',
            'email' => 'jordan@example.com',
            'topic' => 'product-question',
            'message' => 'Please find the attached artwork for my request.',
            'company' => '',
            'g-recaptcha-response' => 'test-recaptcha-token',
        ];
    }

    public function test_contact_form_shows_bulk_quote_style_attachment_after_message(): void
    {
        $response = $this->get(route('contact'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('name="attachment"', $html);
        $this->assertStringContainsString('Upload artwork', $html);
        $this->assertStringContainsString('PDF, JPG or PNG (max 10MB)', $html);
        $this->assertTrue(strpos($html, 'id="contact-message"') < strpos($html, 'id="contact-attachment"'));
        $this->assertTrue(strpos($html, 'id="contact-attachment"') < strpos($html, 'class="np-form-bottom-bar"'));
    }

    public function test_optional_contact_attachment_is_stored_privately(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->post(route('contact.store'), [
            ...$this->contactPayload(),
            'attachment' => UploadedFile::fake()->image('jersey-artwork.png'),
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status');

        $message = ContactMessage::firstOrFail();
        $this->assertSame('jersey-artwork.png', $message->attachment_name);
        $this->assertSame('image/png', $message->attachment_mime);
        $this->assertGreaterThan(0, $message->attachment_size);
        $this->assertStringStartsWith('contact-messages/'.$message->id.'/', $message->attachment_path);
        Storage::disk('local')->assertExists($message->attachment_path);
        $this->assertSame([], Storage::disk('public')->allFiles('contact-messages'));
        $this->get(route('admin.contact-page.attachment', $message))
            ->assertRedirect(route('admin.login'));
    }

    public function test_no_attachment_is_still_a_valid_contact_request(): void
    {
        $this->post(route('contact.store'), $this->contactPayload())
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertNull(ContactMessage::firstOrFail()->attachment_path);
    }

    public function test_contact_form_rejects_unsupported_and_oversized_files(): void
    {
        Storage::fake('local');
        $this->post(route('contact.store'), [
            ...$this->contactPayload(),
            'attachment' => UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml'),
        ])->assertRedirect()->assertSessionHasErrors('attachment');

        $this->post(route('contact.store'), [
            ...$this->contactPayload(),
            'attachment' => UploadedFile::fake()->create('oversize.pdf', 10241, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('contact_messages', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('contact-messages'));
    }

    public function test_privileged_admin_can_download_private_contact_attachment(): void
    {
        Storage::fake('local');
        $this->post(route('contact.store'), [
            ...$this->contactPayload(),
            'attachment' => UploadedFile::fake()->image('artwork.png'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $contact = ContactMessage::firstOrFail();
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.contact-page.attachment', $contact))
            ->assertOk()
            ->assertHeader('Content-Disposition');
    }

    public function test_admin_can_hide_and_reenable_only_left_whatsapp_contact_entry(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $service = app(ContactPageService::class);
        $payload = $service->defaults();

        $this->get(route('contact'))->assertOk()->assertDontSee('Chat on WhatsApp');

        $payload['info']['show_whatsapp_contact'] = '0';
        $this->actingAs($admin, 'admin')
            ->put(route('admin.contact-page.update'), $payload)
            ->assertRedirect(route('admin.contact-page.edit'))
            ->assertSessionHasNoErrors();
        $this->assertFalse((bool) data_get($service->settings(), 'info.show_whatsapp_contact'));
        $this->assertSame('0', (string) data_get(
            StorefrontPageSetting::where('page_key', 'contact-us')->firstOrFail()->content,
            'info.show_whatsapp_contact'
        ));
        $this->get(route('contact'))->assertOk()->assertDontSee('Chat on WhatsApp')
            ->assertSee('Instagram')->assertSee('Facebook');

        $payload['info']['show_whatsapp_contact'] = '1';
        $this->put(route('admin.contact-page.update'), $payload)
            ->assertRedirect(route('admin.contact-page.edit'))
            ->assertSessionHasNoErrors();
        $this->get(route('contact'))->assertOk()->assertSee('Chat on WhatsApp');
    }
}
