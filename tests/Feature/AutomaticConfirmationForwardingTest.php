<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionalEmail;
use App\Mail\TransactionalEmail;
use App\Models\BulkQuoteRequest;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Services\Email\AutomaticConfirmationForwarder;
use App\Services\Email\ForwardingSettings;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AutomaticConfirmationForwardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        Queue::fake();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.mailer', 'smtp');
        config()->set('transactional_email.delivery.mode', 'queue');
    }

    public function test_new_quote_is_auto_forwarded_to_saved_address_with_original_file_and_details(): void
    {
        app(ForwardingSettings::class)->save('production@example.com', 'Confirm this request for production.');
        Storage::disk('local')->put('bulk-quotes/artwork.txt', 'Original customer upload');

        $quote = $this->quote([
            'attachment' => [
                'disk' => 'local',
                'path' => 'bulk-quotes/artwork.txt',
                'original_name' => 'artwork.txt',
                'mime_type' => 'text/plain',
            ],
        ]);

        app(TransactionalEmailManager::class)->bulkQuoteReceived($quote);

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail) use ($quote): bool {
            $message = $mail->emailMessage;
            return $message->key === 'admin.confirmation-auto-forward'
                && $message->recipients === [['email' => 'production@example.com']]
                && str_contains($message->subject, $quote->reference)
                && in_array('Confirm this request for production.', $message->introLines, true)
                && collect($message->attachments)->contains(fn (array $file) => $file['name'] === 'request-artwork.txt')
                && collect($message->attachments)->contains(fn (array $file) => str_ends_with($file['name'], '_Details.txt'));
        });
        Queue::assertPushed(SendTransactionalEmail::class);
    }

    public function test_new_order_is_auto_forwarded_with_invoice_and_order_details(): void
    {
        app(ForwardingSettings::class)->save('production@example.com, manager@example.com', 'Confirm the new order.');
        $order = $this->order();

        app(TransactionalEmailManager::class)->orderPlaced($order);

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail) use ($order): bool {
            $message = $mail->emailMessage;
            return $message->key === 'admin.confirmation-auto-forward'
                && $message->recipients === [
                    ['email' => 'production@example.com'],
                    ['email' => 'manager@example.com'],
                ]
                && str_contains($message->subject, $order->order_number)
                && collect($message->attachments)->contains(fn (array $file) => str_ends_with($file['name'], '_Invoice.pdf'))
                && collect($message->attachments)->contains(fn (array $file) => str_ends_with($file['name'], '_Details.txt'));
        });
    }

    public function test_new_order_from_converted_quote_is_forwarded(): void
    {
        app(ForwardingSettings::class)->save('production@example.com', 'Confirm this new order.');
        $quote = $this->quote();
        $order = $this->order();

        app(TransactionalEmailManager::class)->bulkQuoteConverted($quote, $order);

        Mail::assertSent(TransactionalEmail::class, fn (TransactionalEmail $mail) =>
            $mail->emailMessage->key === 'admin.confirmation-auto-forward'
            && $mail->emailMessage->recipients === [['email' => 'production@example.com']]
            && str_contains($mail->emailMessage->subject, $order->order_number));
    }

    public function test_blank_forwarding_recipients_disable_automatic_sending(): void
    {
        app(ForwardingSettings::class)->save('', 'No automatic forwarding.');
        app(AutomaticConfirmationForwarder::class)->quoteCreated($this->quote());

        Mail::assertNothingSent();
    }

    public function test_contact_submission_is_automatically_forwarded_to_saved_recipient_with_message_and_attachment(): void
    {
        app(ForwardingSettings::class)->save('operations@example.com', 'Please review this enquiry.');
        config()->set('services.recaptcha.site_key', 'test-site-key');
        config()->set('services.recaptcha.secret_key', 'test-secret-key');
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'hostname' => parse_url(route('contact.store'), PHP_URL_HOST),
            ], 200),
        ]);

        $body = 'Please contact me about a custom jersey with a detailed artwork attachment.';
        $this->post(route('contact.store'), [
            'name' => 'Customer Contact',
            'email' => 'customer@example.com',
            'topic' => 'product-question',
            'message' => $body,
            'g-recaptcha-response' => 'test-recaptcha-token',
            'attachment' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status');

        $contact = ContactMessage::firstOrFail();
        Storage::disk('local')->assertExists($contact->attachment_path);
        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail) use ($contact, $body): bool {
            $message = $mail->emailMessage;
            return $message->key === 'admin.confirmation-auto-forward'
                && $message->recipients === [['email' => 'operations@example.com']]
                && str_contains($message->subject, 'Contact Us message #'.$contact->id)
                && ($message->details['Message'] ?? null) === $body
                && ($message->details['Customer email'] ?? null) === 'customer@example.com'
                && ($message->details['Download attachment (admin login required)'] ?? null)
                    === route('admin.contact-page.attachment', $contact)
                && in_array('Please review this enquiry.', $message->introLines, true)
                && collect($message->attachments)->contains(fn (array $file) => ($file['name'] ?? '') === 'contact-logo.png')
                && collect($message->attachments)->contains(fn (array $file) => str_ends_with((string) ($file['name'] ?? ''), '_Details.txt'));
        });
    }

    public function test_contact_without_file_is_forwarded_without_upload_attachment(): void
    {
        app(ForwardingSettings::class)->save('operations@example.com', 'New enquiry.');
        $contact = $this->contact();

        app(AutomaticConfirmationForwarder::class)->contactCreated($contact);

        Mail::assertSent(TransactionalEmail::class, fn (TransactionalEmail $mail) =>
            $mail->emailMessage->key === 'admin.confirmation-auto-forward'
            && $mail->emailMessage->recipients === [['email' => 'operations@example.com']]
            && ($mail->emailMessage->details['Message'] ?? null) === $contact->message
            && count($mail->emailMessage->attachments) === 1);
    }

    public function test_large_e2a_contact_attachment_is_linked_privately_instead_of_blocking_forward(): void
    {
        app(ForwardingSettings::class)->save('operations@example.com', 'New enquiry.');
        // Exercise E2A's composed-message attachment limit using a complete,
        // test-only provider configuration. Mail::fake() prevents API calls.
        config()->set('transactional_email.mailer', 'e2a');
        config()->set('mail.mailers.e2a.api_key', 'test-only-e2a-api-key');
        config()->set('mail.mailers.e2a.sender', config('mail.from.address'));
        config()->set('mail.mailers.e2a.api_url', 'https://api.e2a.dev/v1');
        $contact = $this->contact();
        $path = 'contact-messages/'.$contact->id.'/oversized.png';
        Storage::disk('local')->put($path, 'sample stored file content');
        $contact->update([
            'attachment_path' => $path,
            'attachment_name' => 'oversized.png',
            'attachment_mime' => 'image/png',
            'attachment_size' => 9 * 1024 * 1024,
        ]);

        app(AutomaticConfirmationForwarder::class)->contactCreated($contact);

        Mail::assertSent(TransactionalEmail::class, fn (TransactionalEmail $mail) =>
            $mail->emailMessage->key === 'admin.confirmation-auto-forward'
            && ($mail->emailMessage->details['Download attachment (admin login required)'] ?? null)
                === route('admin.contact-page.attachment', $contact)
            && count($mail->emailMessage->attachments) === 1);
    }

    public function test_invalid_contact_submission_never_triggers_automatic_forwarding(): void
    {
        app(ForwardingSettings::class)->save('operations@example.com', 'New enquiry.');

        $this->post(route('contact.store'), [
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'topic' => 'product-question',
            'message' => 'short',
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_blank_recipients_do_not_forward_contact_messages(): void
    {
        app(ForwardingSettings::class)->save('', 'Forwarding disabled.');

        app(AutomaticConfirmationForwarder::class)->contactCreated($this->contact());

        Mail::assertNothingSent();
    }

    private function contact(): ContactMessage
    {
        return ContactMessage::create([
            'name' => 'Contact Sender',
            'email' => 'sender@example.com',
            'topic' => 'product-question',
            'message' => 'Please send a quote for our team uniforms.',
            'status' => ContactMessage::STATUS_NEW,
        ]);
    }

    private function quote(array $attributes = []): BulkQuoteRequest
    {
        return BulkQuoteRequest::create(array_merge([
            'reference' => 'BQ-AUTO-001',
            'status' => BulkQuoteRequest::STATUS_NEW,
            'full_name' => 'Test Customer',
            'organization' => 'Test Team',
            'email' => 'customer@example.com',
            'phone' => '+10000000000',
            'product_type' => 'Jersey',
            'estimated_quantity' => '10-49',
            'sizes_needed' => 'M, L',
            'artwork_details' => 'Team crest on chest',
            'shipping_address' => 'Shipping test address',
            'country' => 'United States',
            'needed_by' => now()->addMonth(),
        ], $attributes));
    }

    private function order(): Order
    {
        return Order::create([
            'order_number' => 'NPS-AUTO-001',
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'subtotal' => 125,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => 125,
            'total_quantity' => 1,
            'placed_at' => now(),
        ]);
    }
}
