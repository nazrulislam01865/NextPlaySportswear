<?php

namespace App\Mail;

use App\Data\EmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
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
            view: 'emails.transactional',
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
        return [];
    }
}
