<?php

namespace Tests\Feature;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use App\Jobs\SendTransactionalEmail;
use App\Mail\TransactionalEmail;
use App\Services\Email\CentralEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CentralizedEmailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_contract_resolves_to_the_central_service(): void
    {
        $this->assertInstanceOf(CentralEmailService::class, app(EmailService::class));
    }

    public function test_central_service_queues_one_provider_agnostic_email_job(): void
    {
        Queue::fake();

        app(EmailService::class)->queue(new EmailMessage(
            key: 'test.central-email',
            recipients: [['email' => 'customer@example.com', 'name' => 'Customer']],
            subject: 'Central email test',
            heading: 'Central email test',
        ));

        Queue::assertPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job): bool {
            return $job->message->key === 'test.central-email'
                && data_get($job->message->recipients, '0.email') === 'customer@example.com';
        });
    }

    public function test_send_now_uses_the_shared_transactional_mailable(): void
    {
        Mail::fake();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.mailer', 'array');

        app(EmailService::class)->sendNow(new EmailMessage(
            key: 'test.send-now',
            recipients: [['email' => 'customer@example.com']],
            subject: 'Central email test',
            heading: 'Central email test',
        ));

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail): bool {
            return $mail->emailMessage->key === 'test.send-now';
        });
    }
}
