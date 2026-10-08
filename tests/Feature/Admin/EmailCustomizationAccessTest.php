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
        $logo = \Illuminate\Http\UploadedFile::fake()->create('brand-logo.png', 50, 'image/png');

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

    public function test_engine_resolves_dynamic_variables_with_context(): void
    {
        $engine = new \App\Services\Email\EmailCustomizationEngine();
        $sample = \App\Services\Email\EmailCustomizationEngine::sampleContext('np-12345');

        $text = 'Hello {{customer_name}}, your order {{order_number}} will arrive on {{updated_estimate}}.';
        $resolved = $engine->resolveVariables($text, $sample);

        $this->assertSame('Hello Jordan Smith, your order #NP-12345 will arrive on Fri, Dec 27, 2026.', $resolved);
    }

    public function test_engine_builds_valid_email_message(): void
    {
        $engine = new \App\Services\Email\EmailCustomizationEngine();
        $template = EmailTemplate::where('key', 'delivery-estimate-updated')->firstOrFail();
        $branding = EmailGlobalBranding::current();
        $context = \App\Services\Email\EmailCustomizationEngine::sampleContext('np-12345');

        $message = $engine->buildEmailMessage($template, $branding, $context, 'test@example.com', 'Test User');

        $this->assertInstanceOf(\App\Data\EmailMessage::class, $message);
        $this->assertSame('test@example.com', $message->recipients[0]['email']);
        $this->assertNotEmpty($message->subject);
    }

    public function test_admin_can_send_test_email(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.email-customization.templates.send-test', 'delivery-estimate-updated'), [
            'recipient_email' => 'admin.test@nextplay.com',
            'sample_order' => 'np-12345',
            'use_sample_data' => '1',
            'use_published_version' => '1',
        ]);

        $response->assertRedirect(route('admin.email-customization.templates.preview', 'delivery-estimate-updated'));
        $response->assertSessionHas('status');
    }

    public function test_admin_can_reorder_and_persist_template_blocks(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $template = EmailTemplate::where('key', 'delivery-estimate-updated')->firstOrFail();

        $reorderedBlocks = [
            [
                'id' => 'order_summary',
                'name' => 'Order Summary First',
                'desc' => 'Shows order summary right at top.',
                'enabled' => '1',
            ],
            [
                'id' => 'greeting',
                'name' => 'Greeting',
                'desc' => 'Personalized customer greeting.',
                'enabled' => '0',
            ],
            [
                'id' => 'delivery_card',
                'name' => 'Delivery Estimate Card',
                'desc' => 'Shows previous and updated delivery dates.',
                'enabled' => '1',
            ],
        ];

        $response = $this->actingAs($admin, 'admin')->put(route('admin.email-customization.templates.update', 'delivery-estimate-updated'), [
            'subject' => $template->subject,
            'heading' => $template->heading,
            'cta_label' => $template->cta_label,
            'cta_url' => $template->cta_url_type,
            'blocks' => $reorderedBlocks,
            'action' => 'draft',
        ]);

        $response->assertRedirect(route('admin.email-customization.templates.edit', 'delivery-estimate-updated'));

        $template->refresh();
        $this->assertCount(3, $template->blocks);
        $this->assertSame('order_summary', $template->blocks[0]['id']);
        $this->assertTrue($template->blocks[0]['enabled']);
        $this->assertSame('greeting', $template->blocks[1]['id']);
        $this->assertFalse($template->blocks[1]['enabled']);
        $this->assertSame('delivery_card', $template->blocks[2]['id']);
        $this->assertTrue($template->blocks[2]['enabled']);
    }

    public function test_order_placed_uses_published_custom_email_template(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        config()->set('transactional_email.delivery.mode', 'queue');
        config()->set('transactional_email.queue.enabled', true);

        $template = EmailTemplate::where('key', 'order-confirmation')->firstOrFail();
        $template->update([
            'status' => 'published',
            'subject' => 'VIP Order {{order_number}} is confirmed!',
            'heading' => 'Welcome to the Club {{customer_name}}',
        ]);

        $order = \App\Models\Order::query()->create([
            'order_number' => 'NP-98765',
            'status' => 'placed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'subtotal' => 199.99,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 199.99,
            'total_quantity' => 2,
            'placed_at' => now(),
        ]);

        app(\App\Services\Email\TransactionalEmailManager::class)->orderPlaced($order);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendTransactionalEmail::class, function ($job) {
            return str_contains($job->message->subject, 'VIP Order NP-98765 is confirmed!')
                && str_contains($job->message->heading, 'Welcome to the Club John Doe');
        });
    }

    public function test_welcome_uses_published_welcome_template(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        config()->set('transactional_email.delivery.mode', 'queue');
        config()->set('transactional_email.queue.enabled', true);

        $template = EmailTemplate::where('key', 'welcome-email')->firstOrFail();
        $template->update([
            'status' => 'published',
            'subject' => 'Welcome to the NextPlay Community, {{customer_name}}!',
        ]);

        $user = User::factory()->create([
            'name' => 'Alice Walker',
            'email' => 'alice@example.com',
        ]);

        app(\App\Services\Email\TransactionalEmailManager::class)->welcome($user);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendTransactionalEmail::class, function ($job) {
            return str_contains($job->message->subject, 'Welcome to the NextPlay Community, Alice Walker!');
        });
    }

    public function test_delivery_estimate_updated_event_dispatches_customized_notification(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        config()->set('transactional_email.delivery.mode', 'queue');
        config()->set('transactional_email.queue.enabled', true);

        $order = \App\Models\Order::query()->create([
            'order_number' => 'NP-55443',
            'status' => 'in_transit',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'currency' => 'USD',
            'customer_name' => 'Sam Rivers',
            'customer_email' => 'sam@example.com',
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100,
            'total_quantity' => 1,
            'placed_at' => now(),
        ]);

        $shipment = \App\Models\OrderShipment::query()->create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-998877',
            'status' => 'in_transit',
            'carrier' => 'UPS',
            'service' => 'Ground',
            'tracking_number' => '1Z111222333',
            'estimated_delivery_at' => now()->addDays(5),
        ]);

        \App\Events\DeliveryEstimateUpdated::dispatch(
            $shipment,
            'Fri, Oct 24, 2026',
            'Severe weather detour.'
        );

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendTransactionalEmail::class, function ($job) {
            return $job->message->key === 'shipment.delivery-estimate-updated'
                && data_get($job->message->recipients, '0.email') === 'sam@example.com'
                && isset($job->message->details['Delay Reason']);
        });
    }

    public function test_draft_template_falls_back_to_default_message(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        config()->set('transactional_email.delivery.mode', 'queue');
        config()->set('transactional_email.queue.enabled', true);

        $template = EmailTemplate::where('key', 'order-confirmation')->firstOrFail();
        $template->update(['status' => 'draft']);

        $order = \App\Models\Order::query()->create([
            'order_number' => 'NP-33221',
            'status' => 'placed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'Fallback User',
            'customer_email' => 'fallback@example.com',
            'subtotal' => 50,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 50,
            'total_quantity' => 1,
            'placed_at' => now(),
        ]);

        app(\App\Services\Email\TransactionalEmailManager::class)->orderPlaced($order);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendTransactionalEmail::class, function ($job) {
            return str_contains($job->message->subject, 'received')
                && data_get($job->message->recipients, '0.email') === 'fallback@example.com';
        });
    }
}
