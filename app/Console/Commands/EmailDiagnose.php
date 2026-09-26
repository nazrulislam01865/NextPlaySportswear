<?php

namespace App\Console\Commands;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class EmailDiagnose extends Command
{
    protected $signature = 'email:diagnose
        {--send= : Send a real transactional test email to this address}';

    protected $description = 'Inspect transactional email configuration and optionally send a real provider test.';

    public function handle(EmailService $emails): int
    {
        $mailerName = trim((string) config(
            'transactional_email.mailer',
            config('mail.default', 'log')
        ));

        $mailer = (array) config('mail.mailers.'.$mailerName, []);
        $transport = trim((string) ($mailer['transport'] ?? 'missing'));
        $deliveryMode = strtolower(trim((string) config(
            'transactional_email.delivery.mode',
            'after_response'
        )));

        $this->info('NextPlay transactional email diagnostics');
        $this->table(['Setting', 'Value'], [
            ['Application environment', app()->environment()],
            ['Transactional email enabled', $emails->enabled() ? 'yes' : 'NO'],
            ['Delivery mode', $deliveryMode !== '' ? $deliveryMode : '(empty)'],
            ['Critical verification synchronous', config('transactional_email.critical.email_verification_sync') ? 'yes' : 'no'],
            ['Critical password reset synchronous', config('transactional_email.critical.password_reset_sync') ? 'yes' : 'no'],
            ['Mailer', $mailerName !== '' ? $mailerName : '(empty)'],
            ['Transport', $transport !== '' ? $transport : '(empty)'],
            ['From address', (string) config('mail.from.address', '')],
            ['From name', (string) config('mail.from.name', '')],
            ['SMTP host', $transport === 'smtp' ? (string) ($mailer['host'] ?? '') : '-'],
            ['SMTP port', $transport === 'smtp' ? (string) ($mailer['port'] ?? '') : '-'],
            ['SMTP scheme', $transport === 'smtp' ? ((string) ($mailer['scheme'] ?? '') ?: 'auto') : '-'],
            ['SMTP username configured', $transport === 'smtp' && filled($mailer['username'] ?? null) ? 'yes' : ($transport === 'smtp' ? 'no' : '-')],
            ['SMTP password configured', $transport === 'smtp' && filled($mailer['password'] ?? null) ? 'yes' : ($transport === 'smtp' ? 'no' : '-')],
            ['Queue connection', (string) config('transactional_email.queue.connection', config('queue.default', ''))],
            ['Queue name', (string) config('transactional_email.queue.name', '') ?: 'default'],
            ['Pending jobs', $this->tableCount('jobs')],
            ['Failed jobs', $this->tableCount('failed_jobs')],
        ]);

        $problems = $this->configurationProblems(
            $emails->enabled(),
            $mailerName,
            $transport,
            $mailer,
            $deliveryMode,
        );

        if ($problems === []) {
            $this->info('Configuration checks passed.');
        } else {
            foreach ($problems as $problem) {
                $this->error($problem);
            }
        }

        if ($deliveryMode === 'queue') {
            $this->warn('Queue mode is enabled. Email delivery additionally depends on a continuously running queue worker.');
        }

        $recipient = strtolower(trim((string) $this->option('send')));

        if ($recipient === '') {
            $this->newLine();
            $this->line('To test the provider directly, run:');
            $this->line('php artisan email:diagnose --send=you@example.com');

            return $problems === [] ? self::SUCCESS : self::FAILURE;
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('The --send value must be a valid email address.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Sending a real provider test to '.$recipient.' ...');

        try {
            // Always bypass deferred/queue delivery for diagnostics. A success
            // result therefore means the configured provider accepted the mail
            // during this command, not merely that a job was created.
            $emails->sendNow(new EmailMessage(
                key: 'diagnostic.provider-test',
                recipients: [['email' => $recipient, 'name' => 'NextPlay Email Test']],
                subject: 'NextPlay transactional email test',
                heading: 'Email delivery test successful',
                introLines: [
                    'This message was sent directly through the transactional email provider configured on your NextPlay server.',
                ],
                details: [
                    'Mailer' => $mailerName,
                    'Transport' => $transport,
                    'Environment' => app()->environment(),
                ],
                outroLines: [
                    'If you received this email, the application-to-provider delivery path is working.',
                ],
            ));
        } catch (Throwable $exception) {
            $this->error('Provider test FAILED: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Provider accepted the test email successfully.');

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $mailer
     * @return array<int, string>
     */
    private function configurationProblems(
        bool $enabled,
        string $mailerName,
        string $transport,
        array $mailer,
        string $deliveryMode,
    ): array {
        $problems = [];

        if (! $enabled) {
            $problems[] = 'TRANSACTIONAL_EMAIL_ENABLED is false.';
        }

        if ($mailerName === '' || $transport === 'missing' || $transport === '') {
            $problems[] = 'The configured transactional mailer does not exist in config/mail.php.';
        }

        if (
            app()->environment('production', 'staging')
            && in_array($transport, ['log', 'array', 'null'], true)
        ) {
            $problems[] = 'The selected mailer does not deliver email. Configure SMTP or another real provider.';
        }

        if ($transport === 'smtp') {
            if (trim((string) ($mailer['host'] ?? '')) === '') {
                $problems[] = 'MAIL_HOST is empty.';
            }

            if ((int) ($mailer['port'] ?? 0) < 1) {
                $problems[] = 'MAIL_PORT is invalid.';
            }
        }

        if (filter_var((string) config('mail.from.address', ''), FILTER_VALIDATE_EMAIL) === false) {
            $problems[] = 'MAIL_FROM_ADDRESS is invalid.';
        }

        if (! in_array($deliveryMode, ['after_response', 'queue', 'sync'], true)) {
            $problems[] = 'TRANSACTIONAL_EMAIL_DELIVERY_MODE must be after_response, queue, or sync.';
        }

        return $problems;
    }

    private function tableCount(string $table): string
    {
        try {
            if (! Schema::hasTable($table)) {
                return 'table missing';
            }

            return (string) DB::table($table)->count();
        } catch (Throwable) {
            return 'unavailable';
        }
    }
}
