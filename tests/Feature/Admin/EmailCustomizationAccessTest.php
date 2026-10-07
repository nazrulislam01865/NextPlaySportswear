<?php

namespace Tests\Feature\Admin;

use App\Models\EmailGlobalBranding;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Support\AdminRbac;
use Database\Seeders\EmailCustomizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailCustomizationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
        $this->seed(EmailCustomizationSeeder::class);
    }

    public function test_super_admin_can_access_email_customization_routes(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.email-customization.templates.index'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.email-customization.branding.edit'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.email-customization.workflow'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.email-customization.templates.edit', 'delivery-estimate-updated'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.email-customization.templates.visibility', 'delivery-estimate-updated'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.email-customization.templates.preview', 'delivery-estimate-updated'))->assertOk();
    }

    public function test_database_is_seeded_with_expected_templates_and_branding(): void
    {
        $this->assertDatabaseCount('email_global_brandings', 1);
        $this->assertDatabaseCount('email_templates', 6);
        $this->assertDatabaseHas('email_templates', [
            'key' => 'delivery-estimate-updated',
            'name' => 'Delivery Estimate Updated',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('email_global_brandings', [
            'header_bg_color' => '#0B2A4A',
            'button_color' => '#F15A2B',
        ]);
    }

    public function test_email_templates_renders_global_branding_button_with_proper_styling(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.email-customization.templates.index'));

        $response->assertOk();
        $response->assertSee('Global Branding Settings');
        $response->assertSee('background-color: #CF5D38 !important', false);
        $response->assertSee('color: #ffffff !important', false);
        $response->assertSee('!text-white');
    }

    public function test_admin_can_update_global_branding_with_logo_and_colors(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $logo = \Illuminate\Http\UploadedFile::fake()->image('brand-logo.png', 200, 60);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.email-customization.branding.update'), [
            'header_bg_color' => '#112233',
            'button_color' => '#E91D33',
            'font_family' => 'Inter',
            'footer_text' => 'Custom updated footer note',
            'support_email' => 'custom-support@nextplay.com',
            'support_phone' => '+1 (555) 999-0000',
            'social_facebook' => 'https://facebook.com/custom',
            'logo' => $logo,
            'action' => 'publish',
        ]);

        $response->assertRedirect(route('admin.email-customization.branding.edit'));
        $response->assertSessionHas('status');

        $branding = EmailGlobalBranding::current();
        $this->assertSame('#112233', $branding->header_bg_color);
        $this->assertSame('#E91D33', $branding->button_color);
        $this->assertSame('custom-support@nextplay.com', $branding->support_email);
        $this->assertNotNull($branding->logo_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($branding->logo_path);

        // Test removing logo
        $this->actingAs($admin, 'admin')->put(route('admin.email-customization.branding.update'), [
            'header_bg_color' => '#112233',
            'button_color' => '#E91D33',
            'support_email' => 'custom-support@nextplay.com',
            'remove_logo' => '1',
            'action' => 'publish',
        ]);

        $branding->refresh();
        $this->assertNull($branding->logo_path);
    }

    public function test_admin_can_update_template_content_and_publish_new_version(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $template = EmailTemplate::where('key', 'order-confirmation')->firstOrFail();
        $initialVersion = $template->active_version;

        $response = $this->actingAs($admin, 'admin')->put(route('admin.email-customization.templates.update', 'order-confirmation'), [
            'subject' => 'New Order Confirmation Subject',
            'preheader' => 'New Preheader',
            'heading' => 'New Heading',
            'intro' => 'New Intro with {{customer_name}}',
            'cta_label' => 'Click Here Now',
            'cta_url' => 'Order Details Page',
            'action' => 'publish',
        ]);

        $response->assertRedirect(route('admin.email-customization.templates.edit', 'order-confirmation'));
        $response->assertSessionHas('status');

        $template->refresh();
        $this->assertSame('New Order Confirmation Subject', $template->subject);
        $this->assertSame('published', $template->status);
        $this->assertNotSame($initialVersion, $template->active_version);
        $this->assertSame('v3.3', $template->active_version);

        // Verify version snapshot creation
        $this->assertDatabaseHas('email_template_versions', [
            'email_template_id' => $template->id,
            'version' => 'v3.3',
            'subject' => 'New Order Confirmation Subject',
        ]);
    }

    public function test_admin_can_save_template_draft(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.email-customization.templates.update', 'order-confirmation'), [
            'subject' => 'Draft Subject Line',
            'heading' => 'Draft Heading',
            'cta_label' => 'Draft Button',
            'cta_url' => 'Custom URL',
            'action' => 'draft',
        ]);

        $response->assertRedirect(route('admin.email-customization.templates.edit', 'order-confirmation'));
        $template = EmailTemplate::where('key', 'order-confirmation')->first();
        $this->assertSame('draft', $template->status);
        $this->assertNotNull($template->draft_version);
    }

    public function test_admin_can_duplicate_template(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.email-customization.templates.duplicate', 'order-confirmation'));

        $copy = EmailTemplate::where('key', 'order-confirmation-copy')->first();
        $this->assertNotNull($copy);
        $response->assertRedirect(route('admin.email-customization.templates.edit', $copy->key));
        $this->assertSame('Order Confirmation (Copy)', $copy->name);
        $this->assertSame('draft', $copy->status);
    }

    public function test_admin_can_update_content_visibility_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.email-customization.templates.visibility.update', 'delivery-estimate-updated'), [
            'show_logo' => '1',
            'show_greeting' => '1',
            'show_previous_estimate' => '1',
            'show_updated_estimate' => '1',
            'show_holiday_reason' => '0',
            'show_social_links' => '1',
            'action' => 'publish',
        ]);

        $response->assertRedirect(route('admin.email-customization.templates.visibility', 'delivery-estimate-updated'));

        $template = EmailTemplate::where('key', 'delivery-estimate-updated')->first();
        $this->assertTrue($template->visibility_settings['show_previous_estimate']);
        $this->assertFalse($template->visibility_settings['show_holiday_reason']);
        $this->assertTrue($template->visibility_settings['show_social_links']);
    }
}
