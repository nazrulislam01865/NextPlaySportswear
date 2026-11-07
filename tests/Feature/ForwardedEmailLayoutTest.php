<?php

namespace Tests\Feature;

use App\Mail\TransactionalEmail;
use App\Models\BulkQuoteRequest;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Services\Email\ConfirmationForwarder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ForwardedEmailLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.mailer', 'smtp');
    }

    public function test_order_forward_has_structured_html_readable_text_and_original_invoice_attachment(): void
    {
        $order = Order::create([
            'order_number' => 'NP-FWD-STRUCTURED',
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'Morgan Sample',
            'customer_email' => 'morgan@example.com',
            'shipping_address' => ['address' => ['first_name' => 'Morgan', 'address_line_1' => '12 Main Street', 'postal_code' => '01234']],
            'billing_address' => ['address' => ['city' => 'London']],
            'shipping_method' => ['title' => 'Standard Shipping', 'estimated_days' => 8],
            'payment_method' => ['title' => 'Card'],
            'information' => ['source' => 'website'],
            'subtotal' => 24.75,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 5,
            'tax_total' => 0,
            'grand_total' => 29.75,
            'total_quantity' => 1,
            'customer_note' => '<script>alert(1)</script>',
            'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_name' => 'Custom Jersey',
            'sku' => 'NP-JERSEY',
            'quantity' => 1,
            'unit_price' => 24.75,
            'line_total' => 24.75,
            'customization' => ['selected_options' => [['label' => 'Colour', 'value' => 'Blue']], 'size' => 'M'],
        ]);

        app(ConfirmationForwarder::class)->order($order, 'production@example.com', 'Please check this order.');

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail): bool {
            $message = $mail->emailMessage;
            $content = $mail->content();
            $html = view($content->view, ['emailMessage' => $message])->render();
            $txt = collect($message->attachments)->first(fn (array $file): bool => str_ends_with($file['name'], '_Details.txt'));

            return $content->view === 'emails.forwarded-request'
                && $content->text === 'emails.forwarded-request-text'
                && str_contains($html, 'Customer &amp; addresses')
                && str_contains($html, 'Shipping &amp; payment')
                && str_contains($html, 'Custom Jersey')
                && str_contains($html, '12 Main Street')
                && str_contains($html, 'Standard Shipping')
                && str_contains($html, 'Blue')
                && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;')
                && ! str_contains($html, '{&quot;first_name&quot;')
                && is_array($txt)
                && str_contains($txt['data'], 'First Name: Morgan')
                && str_contains($txt['data'], 'SKU: NP-JERSEY')
                && ! str_contains($txt['data'], '{"')
                && collect($message->attachments)->contains(fn (array $file): bool => str_ends_with($file['name'], '_Invoice.pdf'));
        });
    }

    public function test_quote_forward_formats_nested_product_information_and_preserves_details_attachment(): void
    {
        $quote = BulkQuoteRequest::create([
            'reference' => 'BQ-FWD-STRUCTURED',
            'status' => BulkQuoteRequest::STATUS_NEW,
            'full_name' => 'Team Buyer',
            'organization' => 'Sample Basketball Club',
            'email' => 'buyer@example.com',
            'phone' => '+10000000000',
            'product_type' => 'Jersey',
            'estimated_quantity' => '24',
            'sizes_needed' => 'M, L, XL',
            'artwork_details' => 'Team artwork supplied for quotation',
            'shipping_address' => '12 Sample Street',
            'country' => 'United States',
            'needed_by' => now()->addMonth(),
            'product_information' => ['selected_products' => [['product_name' => 'Basketball Jersey', 'quantity' => 24, 'color' => 'Orange']]],
            'quotation_details' => ['customization' => ['printing' => 'Heat transfer']],
        ]);

        app(ConfirmationForwarder::class)->quote($quote, 'production@example.com', 'Please review this quote.');

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail): bool {
            $message = $mail->emailMessage;
            $html = view($mail->content()->view, ['emailMessage' => $message])->render();
            $txt = collect($message->attachments)->first(fn (array $file): bool => str_ends_with($file['name'], '_Details.txt'));

            return str_contains($html, 'Products &amp; requirements')
                && str_contains($html, 'Basketball Jersey')
                && str_contains($html, 'Heat transfer')
                && ! str_contains($html, '{&quot;selected_products&quot;')
                && is_array($txt)
                && str_contains($txt['data'], 'Product Name: Basketball Jersey');
        });
    }

    public function test_contact_forward_uses_structured_template_and_keeps_reply_to(): void
    {
        $contact = ContactMessage::create([
            'name' => 'Contact Sender',
            'email' => 'sender@example.com',
            'topic' => 'product-question',
            'message' => "Please confirm stock.\nThank you.",
            'status' => ContactMessage::STATUS_NEW,
        ]);

        app(ConfirmationForwarder::class)->contact($contact, 'production@example.com', 'Review this enquiry.');

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail): bool {
            $message = $mail->emailMessage;
            $html = view($mail->content()->view, ['emailMessage' => $message])->render();

            return $message->replyTo === 'sender@example.com'
                && str_contains($html, 'Enquiry overview')
                && str_contains($html, 'Customer message')
                && str_contains($html, 'Please confirm stock.<br />')
                && count($message->attachments) === 1;
        });
    }
}
