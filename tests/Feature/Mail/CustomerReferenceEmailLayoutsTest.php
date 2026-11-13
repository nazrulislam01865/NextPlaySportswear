<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Mail\TransactionalEmail;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReferenceEmailLayoutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_five_customer_notifications_match_the_supplied_structure_and_use_the_live_website_logo(): void
    {
        config()->set('app.url', 'https://nextplay.example.test');
        config()->set('filesystems.disks.public.url', '/media');
        StorefrontBrandingSetting::query()->create([
            'logo_path' => 'branding/active-header-logo.png',
        ]);
        StorefrontBranding::flushCache();

        $examples = [
            [
                'key' => 'customer.email-changed-old-address',
                'view' => 'emails.customer.email-changed',
                'heading' => 'Your account email changed',
                'intro' => ['The email address on your NextPlay Sportswear customer account was changed.', 'If you did not make this change, contact our support team immediately.'],
                'details' => ['Previous Email' => 'alex.old@example.com', 'New Email' => 'alex@example.com'],
                'action' => 'Contact Support',
                'icon' => 'notification-shield-check',
                'required' => ['Previous Email', 'New Email', 'Keep your account secure', 'If you did not request this update'],
            ],
            [
                'key' => 'artwork.customer-proof-ready',
                'view' => 'emails.customer.artwork-proof-ready',
                'heading' => 'Your artwork proof is ready',
                'intro' => ['Your NextPlay design proof is ready for review. Please approve it or request revisions before production.'],
                'details' => ['Order Number' => 'NP-10482', 'Design' => 'Team Home Kit', 'Proof Version' => 'v2'],
                'action' => 'Review Artwork Proof',
                'icon' => 'notification-artwork',
                'required' => ['Team Home Kit', 'Proof Version', 'Need help?'],
            ],
            [
                'key' => 'shipment.delivery-estimate-updated',
                'view' => 'emails.customer.delivery-estimate-updated',
                'heading' => 'Delivery estimate updated for your order',
                'intro' => ['Hi Alex,', 'Your estimated delivery date has been updated because a scheduled holiday is a non-delivery date for your destination.'],
                'details' => ['Order number' => '#NP-10482', 'Previous estimate' => 'Wed, Nov 18, 2026', 'Updated estimate' => 'Fri, Nov 20, 2026', 'Holiday affecting delivery' => 'Scheduled holiday'],
                'action' => 'View Order',
                'icon' => 'notification-calendar',
                'required' => ['Previous estimate', 'Updated estimate', 'Holiday affecting delivery', 'Order #NP-10482'],
            ],
            [
                'key' => 'artwork.customer-designer-reply',
                'view' => 'emails.customer.designer-replied',
                'heading' => 'Your designer replied',
                'intro' => ['There is a new message about artwork proof v2.'],
                'details' => ['Order Number' => 'NP-10482', 'Proof Version' => 'v2'],
                'action' => 'Read Designer Reply',
                'icon' => 'notification-reply',
                'required' => ['Proof Version', 'Need help?'],
            ],
            [
                'key' => 'contact.customer-confirmation',
                'view' => 'emails.customer.message-received',
                'heading' => 'Your message has been received',
                'intro' => ['Thanks for contacting NextPlay Sportswear. Our support team has received your message and will review it as soon as possible.'],
                'details' => ['Topic' => 'Order Enquiry', 'Order Number' => 'NP-10482'],
                'action' => 'Visit Help Center',
                'icon' => 'notification-envelope',
                'required' => ['Topic', 'Order Enquiry', 'Need help?'],
            ],
        ];

        foreach ($examples as $example) {
            $message = new EmailMessage(
                key: $example['key'],
                recipients: [['email' => 'alex@example.com', 'name' => 'Alex Example']],
                subject: $example['heading'],
                heading: $example['heading'],
                introLines: $example['intro'],
                details: $example['details'],
                actionText: $example['action'],
                actionUrl: 'https://nextplay.example.test/customer/action',
                outroLines: $example['key'] === 'shipment.delivery-estimate-updated' ? ['Thank you for shopping with NextPlay Sportswear.'] : [],
            );

            $this->assertSame($example['view'], (new TransactionalEmail($message))->content()->view);
            $html = view($example['view'], ['emailMessage' => $message])->render();
            foreach ([
                'https://nextplay.example.test/media/branding/active-header-logo.png',
                'https://nextplay.example.test/images/email/'.$example['icon'].'.png',
                $example['heading'],
                'Hi Alex,',
                $example['action'],
                'href="https://nextplay.example.test/customer/action"',
                'This is a transactional message from NextPlay Sportswear.',
                'Contact support.',
                ...$example['required'],
            ] as $expected) {
                $this->assertStringContainsString($expected, $html, $example['key'].' missing: '.$expected);
            }
            $this->assertStringNotContainsString('background:#0f2b4d', $html);
            $this->assertSame(1, substr_count($html, 'Hi Alex,'), $example['key'].' renders duplicate greeting');
        }

        StorefrontBranding::flushCache();
    }

    public function test_other_existing_email_events_are_not_rerouted(): void
    {
        $message = new EmailMessage(
            key: 'order.internal-placed',
            recipients: [['email' => 'customer@example.com']],
            subject: 'Order update',
            heading: 'Your order status changed',
        );

        $this->assertSame('emails.admin.storefront-order', (new TransactionalEmail($message))->content()->view);
    }
}
