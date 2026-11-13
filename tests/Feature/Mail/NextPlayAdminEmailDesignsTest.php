<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Mail\TransactionalEmail;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayAdminEmailDesignsTest extends TestCase
{
    use RefreshDatabase;

    public function test_six_admin_notifications_render_the_reference_layout_with_the_existing_website_logo(): void
    {
        config()->set('app.url', 'https://nextplay.example.test');
        config()->set('filesystems.disks.public.url', '/media');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/new-current-logo.png']);
        StorefrontBranding::flushCache();

        $cases = [
            ['contact.internal-alert', 'support-message', 'notification-envelope', 'New customer support message',
                ['Name' => 'Alex Morgan', 'Email' => 'alex@example.com', 'Phone' => '+8801700000000',
                    'Topic' => 'Order Enquiry', 'Order Number' => 'NP-10482', 'Message' => 'Artwork timeline'],
                'Open Admin Contact'],
            ['quote.internal-alert', 'bulk-quote-request', 'notification-bulk-quote', 'New bulk quote request',
                ['Reference' => 'BQ-00245', 'Customer' => 'Alex Morgan', 'Email' => 'alex@example.com',
                    'Product Type' => 'Custom Football Jersey', 'Estimated Quantity' => '120'],
                'Open Quote in Admin'],
            ['order.internal-placed', 'storefront-order', 'notification-order-received', 'New storefront order',
                ['Order Number' => 'NP-10482', 'Customer' => 'Alex Morgan', 'Customer Email' => 'alex@example.com',
                    'Items' => '3', 'Order Total' => 'BDT 7,250.00'], 'Open Admin Order'],
            ['artwork.internal-proof-reviewed', 'artwork-reviewed', 'notification-artwork', 'Customer reviewed an artwork proof',
                ['Order Number' => 'NP-10482', 'Proof Version' => 'v2', 'Decision' => 'Approved',
                    'Customer Feedback' => 'Looks great'], 'View Admin Order'],
            ['artwork.internal-customer-message', 'artwork-message', 'notification-guest-reply', 'Customer message about artwork',
                ['Order Number' => 'NP-10482', 'Proof Version' => 'v2'], 'View Admin Order'],
            ['return.internal-submitted', 'return-request', 'notification-return', 'New return or exchange request',
                ['Request Number' => 'RET-00128', 'Type' => 'Return', 'Order Number' => 'NP-10482',
                    'Customer' => 'Alex Morgan', 'Customer Email' => 'alex@example.com', 'Status' => 'Pending'],
                'Open Admin Request'],
        ];

        foreach ($cases as [$key, $template, $icon, $heading, $details, $cta]) {
            $email = new EmailMessage(
                key: $key,
                recipients: [['email' => 'admin@example.com', 'name' => 'NextPlay Team']],
                subject: $heading,
                heading: $heading,
                details: $details,
                actionText: $cta,
                actionUrl: 'https://nextplay.example.test/admin/action',
            );
            $view = 'emails.admin.'.$template;
            $this->assertSame($view, (new TransactionalEmail($email))->content()->view);
            $html = view($view, ['emailMessage' => $email])->render();

            foreach ([
                'https://nextplay.example.test/media/branding/new-current-logo.png',
                '/images/email/'.$icon.'.png', $heading, $cta,
                'This is an internal NextPlay Sportswear notification.',
                'Need help?', 'Contact support.', 'href="https://nextplay.example.test/admin/action"',
            ] as $expected) {
                $this->assertStringContainsString($expected, $html, $key.' is missing '.$expected);
            }
            foreach ($details as $value) {
                $this->assertStringContainsString(e($value), $html, $key.' missing detail '.$value);
            }
            $this->assertStringNotContainsString('Hi NextPlay,', $html);
            $this->assertStringNotContainsString('This is a transactional message', $html);
            $this->assertStringNotContainsString('Example file names shown', $html);
        }

        StorefrontBranding::flushCache();
    }

    public function test_real_admin_file_cards_and_artwork_preview_do_not_invent_files(): void
    {
        $base = [
            'key' => 'artwork.internal-proof-reviewed',
            'recipients' => [['email' => 'admin@example.com', 'name' => 'NextPlay Team']],
            'subject' => 'Artwork reviewed',
            'heading' => 'Customer reviewed an artwork proof',
            'details' => ['Order Number' => 'NP-10482', 'Proof Version' => 'v2', 'Decision' => 'Approved'],
        ];
        $withFile = new EmailMessage(...array_merge($base, [
            'attachments' => [[
                'type' => 'link', 'name' => 'Approved-Real-Proof.pdf', 'mime' => 'application/pdf',
                'size' => 1048576, 'description' => 'Artwork proof',
                'download_url' => 'https://nextplay.example.test/admin/orders/NP-10482/artwork-proofs/1/file?download=1',
            ]],
        ]));
        $html = view('emails.admin.artwork-reviewed', ['emailMessage' => $withFile])->render();
        foreach (['Approved artwork file', 'APPROVED', 'v2', 'Files included with this email',
            'Approved-Real-Proof.pdf', '1,024 KB', 'Download'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertCount(0, (new TransactionalEmail($withFile))->attachments());

        $withoutFile = new EmailMessage(...$base);
        $empty = view('emails.admin.artwork-reviewed', ['emailMessage' => $withoutFile])->render();
        $this->assertStringNotContainsString('Files included with this email', $empty);
        $this->assertStringNotContainsString('APPROVED · v2', $empty);
    }

    public function test_customer_and_forwarding_templates_are_unchanged(): void
    {
        foreach ([
            'customer.password-reset' => 'emails.customer-password-reset',
            'customer.password-changed' => 'emails.customer-password-changed',
            'quote.customer-confirmation' => 'emails.customer.bulk-quote-received',
            'return.customer-submitted' => 'emails.customer.return-request-received',
            'admin.confirmation-forward' => 'emails.forwarded-request',
            'order-request.internal-submitted' => 'emails.admin.order-request',
        ] as $key => $view) {
            $email = new EmailMessage($key, [], $key, $key);
            $this->assertSame($view, (new TransactionalEmail($email))->content()->view);
        }
    }
}
