<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Mail\TransactionalEmail;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Completeness contract for the 34 images in Screens.zip. */
class NextPlayAll34EmailScreensAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.url', 'https://nextplay.example.test');
        config()->set('filesystems.disks.public.url', '/media');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/current-website-logo.png']);
        StorefrontBranding::flushCache();
    }

    protected function tearDown(): void
    {
        StorefrontBranding::flushCache();
        parent::tearDown();
    }

    public function test_all_34_reference_screens_have_the_expected_event_mapping_and_live_logo(): void
    {
        // Each entry: screenshot number, event key, view, icon, expected heading.
        // Variant events use current metadata; no new event keys are needed.
        $screens = [
            [1, 'artwork.customer-proof-ready', 'emails.customer.artwork-proof-ready', 'notification-artwork', 'Your artwork proof is ready'],
            [2, 'order-request.customer-status-updated', 'emails.customer.order-request-updated', 'notification-refresh', 'Your order request changed'],
            [3, 'order-request.customer-submitted', 'emails.customer.order-request-received', 'notification-bulk-quote', 'Your request was received'],
            [4, 'contact.customer-confirmation', 'emails.customer.message-received', 'notification-envelope', 'Your message has been received'],
            [5, 'shipment.delivery-estimate-updated', 'emails.customer.delivery-estimate-updated', 'notification-calendar', 'Delivery estimate updated for your order'],
            [6, 'artwork.customer-designer-reply', 'emails.customer.designer-replied', 'notification-reply', 'Your designer replied'],
            [7, 'customer.email-changed-old-address', 'emails.customer.email-changed', 'notification-shield-check', 'Your account email changed'],
            [8, 'order.customer-placed', 'emails.customer.order-received', 'notification-order-received', 'Your order has been received'],
            [9, 'customer.password-changed', 'emails.customer-password-changed', 'password-changed-lock', 'Your password has'],
            [10, 'customer.password-reset', 'emails.customer-password-reset', 'password-changed-lock', 'Reset your password'],
            [11, 'customer.email-verification', 'emails.customer.email-verification', 'notification-shield-check', 'Verify your email address'],
            [12, 'customer.welcome', 'emails.customer.welcome', 'notification-welcome', 'Your email is verified'],
            [13, 'order.customer-status-updated', 'emails.customer.order-status-changed', 'notification-order-box', 'Your order status changed'],
            [14, 'shipment.customer-status-updated', 'emails.customer.shipment-preparing', 'notification-shipment-truck', 'Your shipment is being prepared'],
            [15, 'shipment.customer-status-updated', 'emails.customer.shipment-status-changed', 'notification-shipment-truck', 'Your shipment status changed'],
            [16, 'quote.customer-confirmation', 'emails.customer.bulk-quote-received', 'notification-bulk-quote', 'We received your bulk quote request'],
            [17, 'quote.customer-update', 'emails.customer.bulk-quote-updated', 'notification-refresh', 'Your bulk quotation has been updated'],
            [18, 'quote.customer-converted-order', 'emails.customer.bulk-quote-converted', 'notification-order-received', 'Your quotation has become an order'],
            [19, 'quote.customer-update', 'emails.customer.quote-guest-feedback', 'notification-guest-reply', 'Quotation: Your quote is ready'],
            [20, 'return.customer-submitted', 'emails.customer.return-request-received', 'notification-return', 'Your Return request was received'],
            [21, 'return.customer-submitted', 'emails.customer.exchange-request-received', 'notification-return', 'Your Exchange request was received'],
            [22, 'return.customer-status-updated', 'emails.customer.return-exchange-updated', 'notification-refresh', 'Your return or exchange request changed'],
            [23, 'order-request.customer-submitted', 'emails.customer.order-request-received', 'notification-bulk-quote', 'Your request was received'],
            [24, 'order-request.customer-status-updated', 'emails.customer.order-request-updated', 'notification-refresh', 'Your order request changed'],
            [25, 'contact.internal-alert', 'emails.admin.support-message', 'notification-envelope', 'New customer support message'],
            [26, 'quote.internal-alert', 'emails.admin.bulk-quote-request', 'notification-bulk-quote', 'New bulk quote request'],
            [27, 'order.internal-placed', 'emails.admin.storefront-order', 'notification-order-received', 'New storefront order'],
            [28, 'artwork.internal-proof-reviewed', 'emails.admin.artwork-reviewed', 'notification-artwork', 'Customer reviewed an artwork proof'],
            [29, 'artwork.internal-customer-message', 'emails.admin.artwork-message', 'notification-guest-reply', 'Customer message about artwork'],
            [30, 'return.internal-submitted', 'emails.admin.return-request', 'notification-return', 'New return or exchange request'],
            [31, 'order-request.internal-submitted', 'emails.admin.order-request', 'notification-bulk-quote', 'New customer order request'],
            [32, 'admin.confirmation-auto-forward', 'emails.forwarded-request', 'notification-order-received', 'Forwarded order confirmation'],
            [33, 'admin.confirmation-auto-forward', 'emails.forwarded-request', 'notification-bulk-quote', 'Forwarded quote request'],
            [34, 'admin.confirmation-forward', 'emails.forwarded-request', 'notification-envelope', 'Forwarded contact message'],
        ];

        $this->assertCount(34, $screens);
        foreach ($screens as [$number, $key, $view, $icon, $heading]) {
            $isInternal = $number >= 25;
            $metadata = match ($number) {
                14 => ['shipment_notification' => 'preparing'],
                15 => ['shipment_notification' => 'status_changed'],
                19 => ['quote_audience' => 'guest', 'quote_notification' => 'ready'],
                17 => ['quote_audience' => 'account', 'quote_notification' => 'message'],
                20 => ['request_type' => 'return'],
                21 => ['request_type' => 'exchange'],
                default => [],
            };
            $details = match ($number) {
                32 => ['Order number' => 'NP-10482', 'Customer name' => 'Alex Morgan', 'Items' => '3', 'Grand total' => 'BDT 7250'],
                33 => ['Request reference' => 'BQ-00245', 'Customer name' => 'Alex Morgan', 'Product' => 'Custom Jersey', 'Quantity' => '120'],
                34 => ['Customer name' => 'Alex Morgan', 'Customer email' => 'alex@example.com', 'Subject' => 'Order Enquiry'],
                default => ['Order Number' => 'NP-10482', 'Status' => 'Pending', 'Proof Version' => 'v2'],
            };
            $message = new EmailMessage(
                key: $key,
                recipients: [['email' => 'alex@example.com', 'name' => 'Alex Morgan']],
                subject: $heading,
                heading: $heading,
                introLines: ['Notification details for review.'],
                details: $details,
                actionText: 'View Details',
                actionUrl: 'https://nextplay.example.test/secure/action',
                metadata: $metadata,
            );
            $actualView = (new TransactionalEmail($message))->content()->view;
            $this->assertSame($view, $actualView, 'Screen '.str_pad((string) $number, 2, '0', STR_PAD_LEFT));
            $html = view($actualView, ['emailMessage' => $message])->render();
            $this->assertStringContainsString('https://nextplay.example.test/media/branding/current-website-logo.png', $html, 'Screen '.$number.' logo');
            $this->assertStringContainsString('/images/email/'.$icon.'.png', $html, 'Screen '.$number.' icon');
            $this->assertStringContainsString($heading, $html, 'Screen '.$number.' heading');
            $this->assertStringContainsString('href="https://nextplay.example.test/secure/action"', $html, 'Screen '.$number.' action');
            $this->assertStringContainsString($isInternal ? 'This is an internal NextPlay Sportswear notification.' : 'Contact support.', $html, 'Screen '.$number.' footer');
            if ($isInternal) {
                $this->assertStringNotContainsString('Hi Alex,', $html, 'Screen '.$number.' must not use customer greeting');
            } else {
                $this->assertSame(1, substr_count($html, 'Hi Alex,'), 'Screen '.$number.' greeting');
            }
        }
    }

    public function test_reset_email_uses_the_reference_not_a_hardcoded_wordmark(): void
    {
        $message = new EmailMessage(
            key: 'customer.password-reset',
            recipients: [['email' => 'alex@example.com', 'name' => 'Alex Morgan']],
            subject: 'Reset your NextPlay password',
            heading: 'Reset your password',
            details: ['Link Expires' => '60 minutes'],
            actionUrl: 'https://nextplay.example.test/reset/test-secret',
        );
        $html = view('emails.customer-password-reset', ['emailMessage' => $message])->render();
        foreach (['Reset your password', '60 minutes', 'Didn’t request a password reset?', 'Reset Password',
            'https://nextplay.example.test/media/branding/current-website-logo.png',
            'This is a transactional message from NextPlay Sportswear.'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('letter-spacing:2px;">NEXTPLAY SPORTSWEAR', $html);
        $this->assertStringContainsString('href="https://nextplay.example.test/reset/test-secret"', $html);
    }

    public function test_real_artwork_files_have_authorized_download_links_without_fake_downloads(): void
    {
        $artwork = new EmailMessage(
            key: 'artwork.customer-proof-ready',
            recipients: [['email' => 'alex@example.com', 'name' => 'Alex Morgan']],
            subject: 'Artwork ready',
            heading: 'Your artwork proof is ready',
            actionUrl: 'https://nextplay.example.test/account/artwork-approvals/12',
            attachments: [[
                'type' => 'link', 'name' => 'Proof-v2.pdf', 'mime' => 'application/pdf',
                'size' => 245760, 'description' => 'Artwork proof sent for review',
                'download_url' => 'https://nextplay.example.test/account/artwork-approvals/12/file?download=1',
            ]],
        );
        $html = view('emails.customer.artwork-proof-ready', ['emailMessage' => $artwork])->render();
        $this->assertStringContainsString('Files included with this email', $html);
        $this->assertStringContainsString('Proof-v2.pdf', $html);
        $this->assertStringContainsString('file?download=1', $html);
        $this->assertStringContainsString('>Download</a>', $html);
        $artwork->attachments[0]['download_url'] = null;
        $html = view('emails.customer.artwork-proof-ready', ['emailMessage' => $artwork])->render();
        $this->assertStringNotContainsString('>Download</a>', $html);
        $this->assertStringContainsString('>View file</a>', $html);
    }
    public function test_order_change_file_sections_render_only_when_real_files_are_supplied(): void
    {
        $screens = [
            'order-request.customer-submitted' => 'emails.customer.order-request-received',
            'order-request.customer-status-updated' => 'emails.customer.order-request-updated',
            'order-request.internal-submitted' => 'emails.admin.order-request',
        ];
        foreach ($screens as $key => $view) {
            $message = new EmailMessage(
                key: $key,
                recipients: [['email' => 'alex@example.com', 'name' => 'Alex Morgan']],
                subject: 'Order request', heading: 'Your request was received',
                details: ['Request Number' => 'OCR-271', 'Order Number' => 'NP-10482', 'Status' => 'Pending'],
                actionUrl: 'https://nextplay.example.test/account/orders/271',
            );
            $this->assertStringNotContainsString('Files included with this email', view($view, ['emailMessage' => $message])->render(), $key);
            $message->attachments = [[
                'type' => 'link', 'name' => 'Real-Customer-Reference.pdf',
                'mime' => 'application/pdf', 'size' => 700416,
                'description' => 'Submitted with request',
                'download_url' => 'https://nextplay.example.test/secure/request-file?signature=test',
            ]];
            $html = view($view, ['emailMessage' => $message])->render();
            $this->assertStringContainsString('Files included with this email', $html, $key);
            $this->assertStringContainsString('Real-Customer-Reference.pdf', $html, $key);
            $this->assertStringContainsString('request-file?signature=test', $html, $key);
        }
    }

}
