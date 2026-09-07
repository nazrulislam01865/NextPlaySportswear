<?php

namespace App\Mail;

use App\Data\EmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionalEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmailMessage $emailMessage
    ) {
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
