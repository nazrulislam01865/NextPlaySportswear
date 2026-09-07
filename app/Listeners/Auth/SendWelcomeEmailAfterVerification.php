<?php

namespace App\Listeners\Auth;

use App\Models\User;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendWelcomeEmailAfterVerification
{
    public function __construct(
        private readonly TransactionalEmailManager $emails,
    ) {
    }

    public function handle(Verified $event): void
    {
        if (! $event->user instanceof User || ! $event->user->isCustomer()) {
            return;
        }

        try {
            DB::transaction(function () use ($event): void {
                /** @var User|null $customer */
                $customer = User::query()
                    ->whereKey($event->user->getKey())
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $customer
                    || ! $customer->hasVerifiedEmail()
                    || $customer->welcome_email_sent_at !== null
                ) {
                    return;
                }

                // The welcome message is intentionally sent only after the
                // customer has proved ownership of their email address. The
                // timestamp makes this idempotent across repeated signed-link
                // requests and future email-address re-verification.
                if (! $this->emails->welcome($customer)) {
                    return;
                }

                $customer->forceFill([
                    'welcome_email_sent_at' => now(),
                ])->saveQuietly();
            }, 3);
        } catch (Throwable $exception) {
            // Verification itself must remain successful even if the optional
            // welcome email cannot be queued. Queue failures are logged and can
            // be retried independently without rolling back email verification.
            report($exception);
        }
    }
}
