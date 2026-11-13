<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Mail\TransactionalEmail;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayCustomerEmailDesignsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_six_reference_emails_use_existing_events_live_logo_and_customer_specific_details(): void
    {
        config()->set('app.url', 'https://nextplay.example.test');
        config()->set('filesystems.disks.public.url', '/media');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/site-logo.png']);
        StorefrontBranding::flushCache();

        $cases = [
            [
                'customer.email-changed-old-address', 'emails.customer.email-changed',
                'Your account email changed', 'notification-shield-check',
                ['The email address on your NextPlay Sportswear customer account was changed.', 'If you did not make this change, contact our support team immediately.'],
                ['Previous Email' => 'alex.old@example.com', 'New Email' => 'alex@example.com'],
                'Contact Support', [], 'Keep your account secure',
            ],
            [
                'order.customer-placed', 'emails.customer.order-received',
                'Your order has been received', 'notification-order-received',
                ['Thanks for your order. We have saved your order securely and will keep its status updated as it moves through payment, design, production, and fulfillment.'],
                ['Order Number' => 'NP-10482', 'Order Status' => 'Pending', 'Payment Status' => 'Paid', 'Items' => '3', 'Order Total' => 'BDT 7,250.00'],
                'View Order', [], 'Need help?',
            ],
            [
                'customer.email-verification', 'emails.customer.email-verification',
                'Verify your email address', 'notification-shield-check',
                ['Thanks for creating or updating your NextPlay Sportswear customer account.', 'Confirm that this email address belongs to you before using protected account and checkout features.'],
                ['Account Email' => 'alex@example.com', 'Link Expires' => '60 minutes'],
                'Verify Email Address', ['If you did not create or update this account, you can ignore this email.', 'For your security, do not forward this verification link to anyone.'], 'Need help?',
            ],
            [
                'customer.welcome', 'emails.customer.welcome',
                'Your email is verified - welcome to NextPlay', 'notification-welcome',
                ['Thanks for verifying your email address. Your NextPlay Sportswear customer account is now ready to use.', 'You can securely access your account, saved details, order history, quotes, and checkout.'],
                [], 'Open My Account', ['If you did not create this account, please contact our support team.'], 'Need help?',
            ],
            [
                'order.customer-status-updated', 'emails.customer.order-status-changed',
                'Your order status changed', 'notification-order-box',
                ['There is a new status update for your NextPlay Sportswear order.'],
                ['Order Number' => 'NP-10482', 'Order Status' => 'Processing', 'Payment Status' => 'Paid', 'Fulfillment' => 'Preparing'],
                'View Order', [], 'Need help?',
            ],
            [
                'shipment.customer-status-updated', 'emails.customer.shipment-preparing',
                'Your shipment is being prepared', 'notification-shipment-truck',
                ['We have a new shipping update for your NextPlay Sportswear order.'],
                ['Order Number' => 'NP-10482', 'Shipment' => 'SHP-00418', 'Status' => 'Preparing', 'Carrier' => 'DHL', 'Service' => 'Express'],
                'View Shipment', [], 'Need help?',
            ],
        ];

        foreach ($cases as [$key, $view, $heading, $icon, $intro, $details, $action, $outro, $help]) {
            $message = new EmailMessage(
                key: $key,
                recipients: [['email' => 'alex@example.com', 'name' => 'Alex Example']],
                subject: $heading,
                heading: $heading,
                introLines: $intro,
                details: $details,
                actionText: $action,
                actionUrl: 'https://nextplay.example.test/secure/action',
                outroLines: $outro,
            );

            $this->assertSame($view, (new TransactionalEmail($message))->content()->view, $key);
            $html = view($view, ['emailMessage' => $message])->render();
            $this->assertStringContainsString('https://nextplay.example.test/media/branding/site-logo.png', $html, $key);
            $this->assertStringContainsString('/images/email/'.$icon.'.png', $html, $key);
            $this->assertStringContainsString($heading, $html, $key);
            $this->assertSame(1, substr_count($html, 'Hi Alex,'), $key);
            $this->assertStringContainsString($help, $html, $key);
            $this->assertStringContainsString($action, $html, $key);
            $this->assertStringContainsString('href="https://nextplay.example.test/secure/action"', $html, $key);
            $this->assertStringContainsString('This is a transactional message from NextPlay Sportswear.', $html, $key);
            foreach ($details as $field => $value) {
                $this->assertStringContainsString($field, $html, $key);
                $this->assertStringContainsString($value, $html, $key);
            }
            foreach (array_merge($intro, $outro) as $line) {
                $this->assertStringContainsString($line, $html, $key);
            }
        }

        StorefrontBranding::flushCache();
    }

    public function test_unrelated_customer_emails_and_password_reset_keep_their_original_templates(): void
    {
        foreach ([
            'customer.password-reset' => 'emails.customer-password-reset',
            'customer.password-changed' => 'emails.customer-password-changed',
            'artwork.customer-proof-ready' => 'emails.customer.artwork-proof-ready',
            'contact.customer-confirmation' => 'emails.customer.message-received',
            'admin.confirmation-forward' => 'emails.forwarded-request',
        ] as $key => $expectedView) {
            $message = new EmailMessage(
                key: $key,
                recipients: [['email' => 'alex@example.com', 'name' => 'Alex']],
                subject: $key,
                heading: $key,
            );
            $this->assertSame($expectedView, (new TransactionalEmail($message))->content()->view);
        }
    }
}
