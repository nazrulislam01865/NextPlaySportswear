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
