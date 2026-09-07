<?php

namespace App\Jobs;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTransactionalEmail implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(
        public EmailMessage $message
    ) {
        $this->tries = max(
            1,
            (int) config('transactional_email.queue.tries', 3)
        );

        $this->timeout = max(
            5,
            (int) config('transactional_email.queue.timeout', 30)
        );
    }

    public function handle(EmailService $emails): void
    {
        $emails->sendNow($this->message);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return array_values(array_map(
            static fn (mixed $seconds): int => max(1, (int) $seconds),
            (array) config(
                'transactional_email.queue.backoff',
                [60, 300, 900]
            ),
        ));
    }

    public function failed(Throwable $exception): void
    {
        Log::error(
            'Transactional email job failed permanently.',
            [
                'email_key' => $this->message->key,
                'recipient_count' => count(
                    $this->message->recipients
                ),
                'exception' => $exception::class,
            ]
        );
    }
}
