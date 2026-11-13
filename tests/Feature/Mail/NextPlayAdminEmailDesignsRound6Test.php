<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Mail\TransactionalEmail;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayAdminEmailDesignsRound6Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.url', 'https://nextplay.example.test');
        config()->set('filesystems.disks.public.url', '/media');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/active-website-logo.png']);
        StorefrontBranding::flushCache();
    }

    protected function tearDown(): void
    {
        StorefrontBranding::flushCache();
        parent::tearDown();
    }

    public function test_internal_order_request_uses_the_reference_layout_and_real_existing_fields(): void
    {
        $email = new EmailMessage(
            key: 'order-request.internal-submitted',
            recipients: [['email' => 'admin@example.com', 'name' => 'NextPlay Team']],
            subject: 'New order change request',
            heading: 'New customer order request',
            details: [
                'Request Number' => 'OCR-271',
                'Order Number' => 'NP-10482',
                'Request Type' => 'Order change',
                'Customer' => 'Alex Morgan',
                'Customer Email' => 'alex@example.com',
                'Status' => 'Pending',
            ],
            actionText: 'Open Admin Order',
            actionUrl: 'https://nextplay.example.test/admin/orders/NP-10482',
        );

        $this->assertSame('emails.admin.order-request', (new TransactionalEmail($email))->content()->view);
        $html = view('emails.admin.order-request', ['emailMessage' => $email])->render();
        foreach ([
            'https://nextplay.example.test/media/branding/active-website-logo.png',
            '/images/email/notification-bulk-quote.png',
            'New customer order request', 'A customer submitted an order request that needs review.',
            'Request Number', 'OCR-271', 'Order Number', 'NP-10482', 'Order change',
            'Alex Morgan', 'alex@example.com', 'Pending', 'Open Admin Order',
            'href="https://nextplay.example.test/admin/orders/NP-10482"',
            'This is an internal NextPlay Sportswear notification.', 'Need help?', 'Contact support.',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('Hi NextPlay,', $html);
        $this->assertStringNotContainsString('Files included with this email', $html);
        $this->assertStringNotContainsString('Order-Change-Reference.pdf', $html);
    }

    public function test_forwarded_order_reference_design_preserves_all_original_details_and_files(): void
    {
        $details = [
            'Order number' => 'NP-10482',
            'Customer name' => 'Alex Morgan',
            'Items' => '3',
            'Grand total' => '7250.00 BDT',
        ];
        $attachments = [
            ['type' => 'data', 'name' => 'Order_NP-10482_Details.txt', 'data' => 'Customer and address details', 'mime' => 'text/plain'],
            ['type' => 'data', 'name' => 'Order_NP-10482_Invoice.pdf', 'data' => '%PDF-1.4 test', 'mime' => 'application/pdf'],
        ];
        $email = $this->forwardedMessage('order', $details, $attachments);
        $html = view('emails.forwarded-request', ['emailMessage' => $email])->render();

        $this->assertSame('emails.forwarded-request', (new TransactionalEmail($email))->content()->view);
        $this->assertSame('emails.forwarded-request-text', (new TransactionalEmail($email))->content()->text);
        $this->assertCount(2, (new TransactionalEmail($email))->attachments());
        foreach (['Forwarded order confirmation', '/images/email/notification-order-received.png',
            'NP-10482', 'Alex Morgan', '7250.00 BDT', 'Review Order',
            'Order_NP-10482_Invoice.pdf', 'Original request details',
            'Customer &amp; addresses', 'Shipping &amp; payment', 'Blue Jersey',
            'This is an internal NextPlay Sportswear notification.'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('Example file names shown', $html);
        $this->assertStringNotContainsString('Hi Forwarder,', $html);
    }

    public function test_forwarded_quote_and_contact_render_correct_distinct_screens_and_attachments(): void
    {
        $quote = $this->forwardedMessage('quote', [
            'Request reference' => 'BQ-00245', 'Customer name' => 'Alex Morgan',
            'Product' => 'Custom Football Jersey', 'Quantity' => '120',
        ], [
            ['type' => 'data', 'name' => 'Quote_BQ-00245_Details.txt', 'data' => 'Details', 'mime' => 'text/plain'],
            ['type' => 'data', 'name' => 'Team-Crest-Reference.png', 'data' => 'real bytes', 'mime' => 'image/png'],
        ]);
        $contact = $this->forwardedMessage('contact', [
            'Customer name' => 'Alex Morgan', 'Customer email' => 'alex@example.com',
            'Subject' => 'Order Enquiry', 'Order / quote number' => 'NP-10482',
            'Message' => "Please confirm the artwork timeline.\nThank you.",
        ], [
            ['type' => 'data', 'name' => 'Contact_12_Details.txt', 'data' => 'Details', 'mime' => 'text/plain'],
            ['type' => 'data', 'name' => 'Team-Logo-Reference.png', 'data' => 'real bytes', 'mime' => 'image/png'],
        ]);

        foreach ([
            [$quote, 'Forwarded quote request', 'notification-bulk-quote.png', 'Review Quotation', 'Team-Crest-Reference.png'],
            [$contact, 'Forwarded contact message', 'notification-envelope.png', 'Review Message', 'Team-Logo-Reference.png'],
        ] as [$email, $heading, $icon, $button, $file]) {
            $html = view('emails.forwarded-request', ['emailMessage' => $email])->render();
            foreach ([
                'https://nextplay.example.test/media/branding/active-website-logo.png', $heading,
                '/images/email/'.$icon, $button, $file, 'Files included with this email',
                'Original request details', 'Need help?', 'Contact support.',
            ] as $expected) {
                $this->assertStringContainsString($expected, $html);
            }
            $this->assertSame('emails.forwarded-request', (new TransactionalEmail($email))->content()->view);
            $this->assertCount(2, (new TransactionalEmail($email))->attachments());
        }
        $contactHtml = view('emails.forwarded-request', ['emailMessage' => $contact])->render();
        $this->assertStringContainsString('Please confirm the artwork timeline.<br />', $contactHtml);
        $this->assertStringContainsString('Customer message', $contactHtml);
    }

    public function test_forwarded_message_without_an_original_upload_does_not_invent_one(): void
    {
        $email = $this->forwardedMessage('contact', [
            'Customer name' => 'Alex Morgan',
            'Customer email' => 'alex@example.com',
            'Subject' => 'General Enquiry',
        ], []);
        $html = view('emails.forwarded-request', ['emailMessage' => $email])->render();
        $this->assertStringNotContainsString('Files included with this email', $html);
        $this->assertStringNotContainsString('Customer uploaded file', $html);
    }

    /** @param array<string,string> $details @param array<int,array<string,mixed>> $attachments */
    private function forwardedMessage(string $type, array $details, array $attachments): EmailMessage
    {
        $sections = [
            ['title' => $type === 'order' ? 'Customer & addresses' : ($type === 'quote' ? 'Products & requirements' : 'Customer message'),
                'rows' => [[
                    'label' => $type === 'order' ? 'Customization' : ($type === 'quote' ? 'Product' : 'Message'),
                    'value' => $type === 'order' ? 'Blue Jersey' : ($type === 'quote' ? 'Custom Football Jersey' : ($details['Message'] ?? 'Customer message')),
                    'depth' => 0, 'group' => false,
                ]]],
            ['title' => 'Shipping & payment', 'rows' => [[
                'label' => 'Shipping method', 'value' => 'Standard', 'depth' => 0, 'group' => false,
            ]]],
        ];

        return new EmailMessage(
            key: 'admin.confirmation-auto-forward',
            recipients: [['email' => 'manager@example.com', 'name' => 'Forwarder']],
            subject: 'Forwarded '.$type,
            heading: 'Please confirm request',
            introLines: ['Please review the original details.'],
            details: $details,
            actionText: 'View in NextPlay Admin',
            actionUrl: 'https://nextplay.example.test/admin/action',
            outroLines: ['Automatically forwarded when the request was created.'],
            replyTo: 'alex@example.com',
            attachments: $attachments,
            sections: $sections,
        );
    }
}
