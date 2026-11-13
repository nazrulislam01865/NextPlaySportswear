<?php

namespace App\Mail;

use App\Data\EmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionalEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmailMessage $emailMessage
    ) {
        // Old queued EmailMessage instances created before the transport was
        // deployed may not yet contain this new property.
        if ($this->emailMessage->deliveryId === '') {
            $this->emailMessage->deliveryId = (string) Str::uuid();
        }

        $this->withSymfonyMessage(function (Email $email): void {
            $email->getHeaders()->addTextHeader(
                'X-NextPlay-Delivery-ID',
                $this->emailMessage->deliveryId,
            );
        });
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailMessage->subject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: match ($this->emailMessage->key) {
                'admin.confirmation-auto-forward', 'admin.confirmation-forward' => 'emails.forwarded-request',
                'contact.internal-alert' => 'emails.admin.support-message',
                'quote.internal-alert' => 'emails.admin.bulk-quote-request',
                'order.internal-placed' => 'emails.admin.storefront-order',
                'artwork.internal-proof-reviewed' => 'emails.admin.artwork-reviewed',
                'artwork.internal-customer-message' => 'emails.admin.artwork-message',
                'return.internal-submitted' => 'emails.admin.return-request',
                'order-request.internal-submitted' => 'emails.admin.order-request',
                'customer.password-reset' => 'emails.customer-password-reset',
                'customer.password-changed' => 'emails.customer-password-changed',
                'customer.email-changed-old-address' => 'emails.customer.email-changed',
                'customer.email-verification' => 'emails.customer.email-verification',
                'customer.welcome' => 'emails.customer.welcome',
                'order.customer-placed' => 'emails.customer.order-received',
                'order.customer-status-updated' => 'emails.customer.order-status-changed',
                'shipment.customer-status-updated' => (
                    ($this->emailMessage->metadata['shipment_notification'] ?? null) === 'preparing'
                    || (!isset($this->emailMessage->metadata['shipment_notification'])
                        && strcasecmp((string) ($this->emailMessage->details['Status'] ?? ''), 'Preparing') === 0)
                ) ? 'emails.customer.shipment-preparing' : 'emails.customer.shipment-status-changed',
                'quote.customer-confirmation' => 'emails.customer.bulk-quote-received',
                'quote.customer-update' => (
                    ($this->emailMessage->metadata['quote_audience'] ?? null) === 'guest'
                    || (!isset($this->emailMessage->metadata['quote_audience'])
                        && $this->emailMessage->actionText === 'Track Quote')
                ) && (
                    ($this->emailMessage->metadata['quote_notification'] ?? null) === 'ready'
                    || (!isset($this->emailMessage->metadata['quote_notification'])
                        && $this->emailMessage->heading === 'Your quotation is ready')
                ) ? 'emails.customer.quote-guest-feedback' : 'emails.customer.bulk-quote-updated',
                'quote.customer-converted-order' => 'emails.customer.bulk-quote-converted',
                'return.customer-status-updated' => 'emails.customer.return-exchange-updated',
                'order-request.customer-submitted' => 'emails.customer.order-request-received',
                'order-request.customer-status-updated' => 'emails.customer.order-request-updated',
                'return.customer-submitted' => (
                    ($this->emailMessage->metadata['request_type'] ?? null) === 'exchange'
                    || (!isset($this->emailMessage->metadata['request_type'])
                        && str_contains(strtolower($this->emailMessage->heading), 'exchange'))
                ) ? 'emails.customer.exchange-request-received' : 'emails.customer.return-request-received',
                'artwork.customer-proof-ready' => 'emails.customer.artwork-proof-ready',
                'artwork.customer-designer-reply' => 'emails.customer.designer-replied',
                'contact.customer-confirmation' => 'emails.customer.message-received',
                'shipment.delivery-estimate-updated' => 'emails.customer.delivery-estimate-updated',
                default => 'emails.transactional',
            },
            text: in_array($this->emailMessage->key, ['admin.confirmation-auto-forward', 'admin.confirmation-forward'], true)
                ? 'emails.forwarded-request-text' : null,
            with: [
                'emailMessage' => $this->emailMessage,
            ],
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->emailMessage->attachments ?? [] as $spec) {
            $name = basename(str_replace('\\', '/', (string) ($spec['name'] ?? 'attachment')));
            if (($spec['type'] ?? '') === 'data' && is_string($spec['data'] ?? null)) {
                $attachments[] = Attachment::fromData(static fn (): string => $spec['data'], $name)
                    ->withMime((string) ($spec['mime'] ?? 'application/pdf'));
            } elseif (($spec['type'] ?? '') === 'storage' && ($spec['disk'] ?? '') === 'local'
                && Storage::disk('local')->exists((string) ($spec['path'] ?? ''))) {
                $attachments[] = Attachment::fromStorageDisk('local', $spec['path'])
                    ->as($name)->withMime((string) ($spec['mime'] ?? 'application/octet-stream'));
            }
        }
        return $attachments;
    }
}
