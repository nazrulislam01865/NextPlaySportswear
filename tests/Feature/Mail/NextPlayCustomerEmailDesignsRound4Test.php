<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Mail\TransactionalEmail;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextPlayCustomerEmailDesignsRound4Test extends TestCase
{
    use RefreshDatabase;

    public function test_three_new_customer_email_designs_use_the_existing_events_and_website_logo(): void
    {
        config()->set('app.url', 'https://nextplay.example.test');
        config()->set('filesystems.disks.public.url', '/media');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/current-logo.png']);
        StorefrontBranding::flushCache();

        $cases = [
            [
                'return.customer-status-updated', 'emails.customer.return-exchange-updated',
                'Your return or exchange request changed', 'notification-refresh',
                'There is a new update on your return or exchange request.',
                ['Request Number' => 'RET-00128', 'Order Number' => 'NP-10482',
                    'Status' => 'Approved', 'Approved Amount' => 'BDT 1,450.00',
                    'Refund Status' => 'Processing', 'Refund Amount' => 'BDT 1,450.00'],
                'View Request',
            ],
            [
                'order-request.customer-submitted', 'emails.customer.order-request-received',
                'Your request was received', 'notification-bulk-quote',
                'Our team will review the request before any production or fulfillment changes are applied.',
                ['Request Number' => 'OCR-272', 'Order Number' => 'NP-10482',
                    'Request Type' => 'Cancellation', 'Status' => 'Pending'],
                'View Order',
            ],
            [
                'order-request.customer-status-updated', 'emails.customer.order-request-updated',
                'Your order request changed', 'notification-refresh',
                'There is a new update on your order change or cancellation request.',
                ['Request Number' => 'OCR-272', 'Order Number' => 'NP-10482',
                    'Status' => 'Rejected'],
                'View Order',
            ],
        ];

        foreach ($cases as [$key, $view, $heading, $icon, $intro, $details, $action]) {
            $message = new EmailMessage(
                key: $key,
                recipients: [['email' => 'alex@example.com', 'name' => 'Alex Example']],
                subject: $heading,
                heading: $heading,
                introLines: [$intro],
                details: $details,
                actionText: $action,
                actionUrl: 'https://nextplay.example.test/customer/action',
            );

            $this->assertSame($view, (new TransactionalEmail($message))->content()->view, $key);
            $html = view($view, ['emailMessage' => $message])->render();
            foreach ([
                'https://nextplay.example.test/media/branding/current-logo.png',
                '/images/email/'.$icon.'.png',
                $heading, 'Hi Alex,', $intro, $action,
                'href="https://nextplay.example.test/customer/action"',
                'Need help?', 'Contact support.',
                'This is a transactional message from NextPlay Sportswear.',
            ] as $expected) {
                $this->assertStringContainsString($expected, $html, $key.' missing '.$expected);
            }
            foreach ($details as $label => $value) {
                $this->assertStringContainsString($label, $html, $key);
                $this->assertStringContainsString($value, $html, $key);
            }
            $this->assertSame(1, substr_count($html, 'Hi Alex,'), $key);
            $this->assertStringNotContainsString('Files included with this email', $html);
        }

        StorefrontBranding::flushCache();
    }

    public function test_return_update_file_card_displays_only_provided_evidence_using_the_secure_link(): void
    {
        $message = new EmailMessage(
            key: 'return.customer-status-updated',
            recipients: [['email' => 'alex@example.com', 'name' => 'Alex']],
            subject: 'Request update',
            heading: 'Your return or exchange request changed',
            details: ['Request Number' => 'RET-00128', 'Order Number' => 'NP-10482', 'Status' => 'Approved'],
            actionText: 'View Request',
            actionUrl: 'https://nextplay.example.test/account/returns/128',
            attachments: [[
                'type' => 'link',
                'name' => 'Return-Evidence.jpg',
                'mime' => 'image/jpeg',
                'size' => 536576,
                'description' => 'Submitted photo reference',
                'download_url' => 'https://nextplay.example.test/account/return-attachments/28?signature=valid',
            ]],
        );
        $html = view('emails.customer.return-exchange-updated', ['emailMessage' => $message])->render();
        foreach ([
            'Files included with this email', 'Return-Evidence.jpg', 'JPG', '524 KB',
            'Submitted photo reference', 'Download', 'signature=valid',
        ] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertCount(0, (new TransactionalEmail($message))->attachments());
        $this->assertStringNotContainsString('Example file names shown', $html);
    }

    public function test_legacy_security_order_and_quote_email_mappings_remain_unchanged(): void
    {
        foreach ([
            'customer.password-reset' => 'emails.customer-password-reset',
            'customer.password-changed' => 'emails.customer-password-changed',
            'quote.customer-confirmation' => 'emails.customer.bulk-quote-received',
            'quote.customer-converted-order' => 'emails.customer.bulk-quote-converted',
            'order.customer-placed' => 'emails.customer.order-received',
            'order-request.internal-submitted' => 'emails.admin.order-request',
        ] as $key => $view) {
            $email = new EmailMessage(key: $key, recipients: [], subject: $key, heading: $key);
            $this->assertSame($view, (new TransactionalEmail($email))->content()->view, $key);
        }
    }
}
