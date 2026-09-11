<?php

namespace App\Services\Email;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use App\Jobs\SendTransactionalEmail;
use App\Mail\TransactionalEmail;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class CentralEmailService implements EmailService
{
    public function __construct(
        private readonly MailFactory $mailManager
    ) {
    }

    public function enabled(): bool
    {
        return (bool) config(
            'transactional_email.enabled',
            true
        );
    }

    public function sendNow(EmailMessage $message): void
    {
        if (! $this->enabled()) {
            return;
        }

        $recipients = $this->validRecipients($message);

        if ($recipients === []) {
            throw new InvalidArgumentException(
                'Transactional email has no valid recipient address.'
            );
        }

        $mailerName = trim(
            (string) config(
                'transactional_email.mailer',
                config('mail.default')
            )
        );

        $mailerName = $mailerName !== ''
            ? $mailerName
            : (string) config('mail.default', 'log');

        $mailable = new TransactionalEmail($message);

        foreach ($recipients as $recipient) {
            $mailable->to(
                $recipient['email'],
                $recipient['name'] !== ''
                    ? $recipient['name']
                    : null,
            );
        }

        if ($this->validEmail($message->replyTo)) {
            $mailable->replyTo(
                (string) $message->replyTo,
                trim((string) $message->replyToName) ?: null,
            );
        }

        $this->mailManager
            ->mailer($mailerName)
            ->send($mailable);

        Log::info(
            'Transactional email sent.',
            [
                'email_key' => $message->key,
                'mailer' => $mailerName,
                'recipient_count' => count($recipients),
            ]
        );
    }

    public function queue(EmailMessage $message): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (! (bool) config(
            'transactional_email.queue.enabled',
            true
        )) {
            $this->sendNow($message);

            return;
        }

        $pending = SendTransactionalEmail::dispatch(
            $message
        );

        $connection = trim(
            (string) config(
                'transactional_email.queue.connection',
                ''
            )
        );

        if ($connection !== '') {
            $pending->onConnection($connection);
        }

        $queue = trim(
            (string) config(
                'transactional_email.queue.name',
                ''
            )
        );

        if ($queue !== '') {
            $pending->onQueue($queue);
        }

        /*
         * Prevent the worker from sending an email
         * before the related DB transaction commits.
         */
        $pending->afterCommit();
    }

    public function safelyQueue(
        EmailMessage $message
    ): bool {
        try {
            $this->queue($message);

            return true;
        } catch (Throwable $exception) {
            Log::error(
                'Transactional email could not be queued.',
                [
                    'email_key' => $message->key,
                    'recipient_count' => count(
                        $message->recipients
                    ),
                    'exception' => $exception::class,
                ]
            );

            report($exception);

            return false;
        }
    }

    /**
     * @return array<int, array{email: string, name: string}>
     */
    private function validRecipients(
        EmailMessage $message
    ): array {
        $recipients = [];
        $seen = [];

        foreach ($message->recipients as $recipient) {
            $email = strtolower(
                trim(
                    (string) (
                        $recipient['email'] ?? ''
                    )
                )
            );

            if (
                ! $this->validEmail($email)
                || isset($seen[$email])
            ) {
                continue;
            }

            $seen[$email] = true;

            $name = trim(
                (string) (
                    $recipient['name'] ?? ''
                )
            );

            $recipients[] = [
                'email' => $email,
                'name' => $name,
            ];
        }

        return $recipients;
    }

    private function validEmail(
        ?string $email
    ): bool {
        return is_string($email)
            && $email !== ''
            && filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) !== false;
    }
}
