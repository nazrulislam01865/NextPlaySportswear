<?php

namespace App\Contracts;

use App\Data\EmailMessage;

interface EmailService
{
    public function enabled(): bool;

    /**
     * Send immediately using the centrally configured Laravel mailer.
     *
     * @throws \Throwable
     */
    public function sendNow(EmailMessage $message): void;

    /**
     * Queue the email when queueing is enabled, otherwise send immediately.
     *
     * @throws \Throwable
     */
    public function queue(EmailMessage $message): void;

    /**
     * Queue a non-critical transactional email without allowing mail failures
     * to break the business action that triggered it.
     */
    public function safelyQueue(EmailMessage $message): bool;
}
