<?php

namespace App\Services\Email;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use App\Jobs\SendTransactionalEmail;
use App\Mail\TransactionalEmail;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
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

        $mailerName = $this->resolvedMailerName();
        $this->assertDeliverableMailer($mailerName);

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

        try {
            $this->mailManager
                ->mailer($mailerName)
                ->send($mailable);
        } catch (Throwable $exception) {
            Log::error(
                'Transactional email delivery failed.',
                [
                    'email_key' => $message->key,
                    'mailer' => $mailerName,
                    'recipient_count' => count($recipients),
                    'exception' => $exception::class,
                    'error' => $this->safeErrorMessage($exception),
                ]
            );

            throw $exception;
        }

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

        // Validate before deferring/queuing so production cannot silently
        // accept email work while using a non-delivery transport such as log.
        $this->assertDeliverableMailer($this->resolvedMailerName());

        $mode = strtolower(trim((string) config(
            'transactional_email.delivery.mode',
            'after_response'
        )));

        if ($mode === 'sync') {
            $this->sendNow($message);

            return;
        }

        if ($mode === 'after_response') {
            // Queue workers and Artisan commands do not have an HTTP response
            // lifecycle to terminate. Send directly in console contexts so
            // after-response mail cannot be stranded until a long-running
            // worker process exits.
            if (app()->runningInConsole()) {
                $this->sendNow($message);

                return;
            }

            SendTransactionalEmail::dispatchAfterResponse($message);

            return;
        }

        if ($mode !== 'queue') {
            throw new InvalidArgumentException(
                'Unsupported transactional email delivery mode: '.$mode
            );
        }

        // This legacy switch remains available for deployments that explicitly
        // select queue mode but temporarily need synchronous delivery.
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
         * Prevent the worker from sending an email before the related DB
         * transaction commits.
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
                'Transactional email could not be dispatched.',
                [
                    'email_key' => $message->key,
                    'recipient_count' => count(
                        $message->recipients
                    ),
                    'exception' => $exception::class,
                    'error' => $this->safeErrorMessage($exception),
                ]
            );

            report($exception);

            return false;
        }
    }

    private function resolvedMailerName(): string
    {
        $mailerName = trim(
            (string) config(
                'transactional_email.mailer',
                config('mail.default')
            )
        );

        return $mailerName !== ''
            ? $mailerName
            : (string) config('mail.default', 'log');
    }

    private function assertDeliverableMailer(string $mailerName): void
    {
        $mailer = config('mail.mailers.'.$mailerName);

        if (! is_array($mailer)) {
            throw new RuntimeException(
                'Transactional email mailer "'.$mailerName.'" is not configured.'
            );
        }

        $fromAddress = trim((string) config('mail.from.address', ''));

        if (! $this->validEmail($fromAddress)) {
            throw new RuntimeException(
                'MAIL_FROM_ADDRESS must contain a valid email address.'
            );
        }

        // log/array are useful for development and tests but they do not
        // deliver mail. In production/staging they must never be treated as a
        // successful transactional email provider.
        if (
            app()->environment('production', 'staging')
            && ! $this->mailerCanDeliver($mailerName)
        ) {
            throw new RuntimeException(
                'Transactional email is configured with a non-delivery mailer. Configure SMTP or another real provider.'
            );
        }

        $transport = strtolower(trim((string) ($mailer['transport'] ?? '')));

        if ($transport === 'smtp') {
            $host = trim((string) ($mailer['host'] ?? ''));
            $port = (int) ($mailer['port'] ?? 0);

            if ($host === '' || $port < 1) {
                throw new RuntimeException(
                    'SMTP mailer requires a valid MAIL_HOST and MAIL_PORT.'
                );
            }
        }
    }

    private function mailerCanDeliver(string $mailerName, array $seen = []): bool
    {
        if (isset($seen[$mailerName])) {
            return false;
        }

        $seen[$mailerName] = true;
        $mailer = config('mail.mailers.'.$mailerName);

        if (! is_array($mailer)) {
            return false;
        }

        $transport = strtolower(trim((string) ($mailer['transport'] ?? '')));

        if (in_array($transport, ['log', 'array', 'null'], true)) {
            return false;
        }

        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            foreach ((array) ($mailer['mailers'] ?? []) as $childMailer) {
                $childMailer = trim((string) $childMailer);

                if (
                    $childMailer !== ''
                    && $this->mailerCanDeliver($childMailer, $seen)
                ) {
                    return true;
                }
            }

            return false;
        }

        return $transport !== '';
    }

    private function safeErrorMessage(Throwable $exception): string
    {
        // Provider errors are valuable operationally, but keep the log entry
        // compact and never include message bodies or recipients here.
        return mb_substr(trim($exception->getMessage()), 0, 500);
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
