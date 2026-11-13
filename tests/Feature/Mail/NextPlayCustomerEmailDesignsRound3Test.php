<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Mail\TransactionalEmail;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayCustomerEmailDesignsRound3Test extends TestCase
{
    use RefreshDatabase;

    public function test_the_seven_reference_notifications_use_existing_events_logo_and_real_details(): void
    {
        config()->set('app.url', 'https://nextplay.example.test');
        config()->set('filesystems.disks.public.url', '/media');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/current-logo.png']);
        StorefrontBranding::flushCache();

        $examples = [
            [
                'shipment.customer-status-updated', ['shipment_notification' => 'status_changed'],
                'Your shipment status changed', 'emails.customer.shipment-status-changed',
                'notification-shipment-truck',
                ['Order Number' => 'NP-10482', 'Shipment' => 'SHP-00418', 'Status' => 'In Transit', 'Carrier' => 'DHL', 'Tracking Number' => 'DHL-420174', 'Estimated Delivery' => 'Nov 20, 2026'],
                'Track Shipment', ['DHL-420174', 'Estimated Delivery', 'We have a new shipping update'],
            ],
            [
                'quote.customer-confirmation', [],
                'We received your bulk quote request', 'emails.customer.bulk-quote-received',
                'notification-bulk-quote',
                ['Reference' => 'BQ-00245', 'Product Type' => 'Custom Football Jersey', 'Estimated Quantity' => '120', 'Status' => 'New'],
                'Track Your Quote', ['Quotation Reference', 'Pending review', 'Quantity'],
            ],
            [
                'quote.customer-update', ['quote_audience' => 'account'],
                'Your bulk quotation has been updated', 'emails.customer.bulk-quote-updated',
                'notification-refresh',
                ['Quote request' => 'BQ-00245', 'Status' => 'Quotation Sent', 'Product' => 'Custom Football Jersey'],
                'View My Bulk Quotes', ['Quote prepared', 'Quotation Reference'],
            ],
            [
                'quote.customer-converted-order', [],
                'Your quotation has become an order', 'emails.customer.bulk-quote-converted',
                'notification-order-received',
                ['Quotation Reference' => 'BQ-00245', 'Order Number' => 'NP-10482', 'Product' => 'Custom Football Jersey', 'Quantity' => '120', 'Shipping Method' => 'Express', 'Order Total' => 'BDT 42,000.00', 'Payment Status' => 'Pending', 'Order Status' => 'Pending'],
                'View Order & Tracking', ['Shipping Method', 'Order Total'],
            ],
            [
                'quote.customer-update', ['quote_audience' => 'guest', 'quote_notification' => 'ready'],
                'Quotation: Your quote is ready', 'emails.customer.quote-guest-feedback',
                'notification-guest-reply',
                ['Quote request' => 'BQ-00245', 'Status' => 'Quotation Sent', 'Product' => 'Custom Football Jersey'],
                'Track Quote', ['Ready for review', 'Quotation Reference'],
            ],
            [
                'return.customer-submitted', ['request_type' => 'return'],
                'Your Return request was received', 'emails.customer.return-request-received',
                'notification-return',
                ['Request Number' => 'RTN-00128', 'Order Number' => 'NP-10482', 'Status' => 'Requested'],
                'View Request', ['Pending', 'Request Number'],
            ],
            [
                'return.customer-submitted', ['request_type' => 'exchange'],
                'Your Exchange request was received', 'emails.customer.exchange-request-received',
                'notification-return',
                ['Request Number' => 'EXC-00128', 'Order Number' => 'NP-10482', 'Status' => 'Requested'],
                'View Request', ['Pending', 'Request Number'],
            ],
        ];

        foreach ($examples as [$key, $meta, $heading, $view, $icon, $details, $button, $expected]) {
            $message = new EmailMessage(
                key: $key,
                recipients: [['email' => 'alex@example.com', 'name' => 'Alex Example']],
                subject: $heading,
                heading: $heading,
                details: $details,
                actionText: $button,
                actionUrl: 'https://nextplay.example.test/customer/action',
                metadata: $meta,
            );

            $this->assertSame($view, (new TransactionalEmail($message))->content()->view, $key);
            $html = view($view, ['emailMessage' => $message])->render();
            foreach ([
                'https://nextplay.example.test/media/branding/current-logo.png',
                '/images/email/'.$icon.'.png', $heading, 'Hi Alex,', e($button),
                'href="https://nextplay.example.test/customer/action"',
                'This is a transactional message from NextPlay Sportswear.',
                'Contact support.', ...$expected,
            ] as $expectedText) {
                $this->assertStringContainsString($expectedText, $html, $key.' missing '.$expectedText);
            }
            $this->assertSame(1, substr_count($html, 'Hi Alex,'), $key);
        }

        StorefrontBranding::flushCache();
    }

    public function test_file_cards_show_only_real_files_and_download_via_secure_links(): void
    {
        $message = new EmailMessage(
            key: 'return.customer-submitted',
            recipients: [['email' => 'alex@example.com', 'name' => 'Alex']],
            subject: 'Return received',
            heading: 'Your Return request was received',
            details: ['Request Number' => 'RTN-00128'],
            actionText: 'View Request',
            actionUrl: 'https://nextplay.example.test/account/returns/RTN-00128',
            metadata: ['request_type' => 'return'],
            attachments: [[
                'type' => 'link',
                'name' => 'Return-Evidence.jpg',
                'mime' => 'image/jpeg',
                'size' => 536576,
                'description' => 'Evidence you submitted',
                'download_url' => 'https://nextplay.example.test/account/return-attachments/28?signature=valid',
            ]],
        );

        $html = view('emails.customer.return-request-received', ['emailMessage' => $message])->render();
        foreach (['Files included with this email', 'Return-Evidence.jpg', 'JPG', '524 KB',
            'Evidence you submitted', 'Download', 'signature=valid'] as $item) {
            $this->assertStringContainsString($item, $html);
        }
        $this->assertCount(0, (new TransactionalEmail($message))->attachments());

        $empty = new EmailMessage(
            key: 'return.customer-submitted',
            recipients: [['email' => 'alex@example.com', 'name' => 'Alex']],
            subject: 'Return received', heading: 'Your Return request was received',
            metadata: ['request_type' => 'return'],
        );
        $html = view('emails.customer.return-request-received', ['emailMessage' => $empty])->render();
        $this->assertStringNotContainsString('Files included with this email', $html);
        $this->assertStringNotContainsString('Return-Evidence.jpg', $html);
    }

    public function test_preparing_shipment_and_unrelated_email_events_keep_existing_mapping(): void
    {
        foreach ([
            ['shipment.customer-status-updated', ['shipment_notification' => 'preparing'], 'emails.customer.shipment-preparing'],
            ['customer.password-reset', [], 'emails.customer-password-reset'],
            ['customer.email-verification', [], 'emails.customer.email-verification'],
            ['artwork.customer-proof-ready', [], 'emails.customer.artwork-proof-ready'],
            ['order.customer-placed', [], 'emails.customer.order-received'],
            ['quote.internal-feedback', [], 'emails.transactional'],
            ['return.customer-status-updated', [], 'emails.customer.return-exchange-updated'],
        ] as [$key, $meta, $view]) {
            $message = new EmailMessage(key: $key, recipients: [], subject: $key, heading: $key, metadata: $meta);
            $this->assertSame($view, (new TransactionalEmail($message))->content()->view, $key);
        }
    }
}
