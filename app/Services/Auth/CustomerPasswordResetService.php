<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class CustomerPasswordResetService
{
    public function __construct(
        private readonly TransactionalEmailManager $emails,
    ) {
    }

    /**
     * Request a reset link only for an active storefront customer.
     *
     * The controller intentionally converts InvalidUser into the same generic
     * success message used for valid accounts so the endpoint cannot be used
     * to enumerate registered customer email addresses.
     */
    public function sendResetLink(
        string $email
    ): string {
        /*
        * Check the local users table first.
        *
        * Only active storefront customers are eligible.
        */
        $customer = $this->activeCustomer($email);


        /*
        * Stop here.
        *
        * Laravel's password broker is never called and
        * therefore no reset email is created for an
        * unknown/inactive/admin account.
        */
        if (! $customer) {
            return Password::InvalidUser;
        }


        $broker = Password::broker('users');


        try {

            return $broker->sendResetLink([
                'email' => $email,
            ]);

        } catch (Throwable $exception) {

            /*
            * Laravel creates the reset token BEFORE asking
            * the User model to send the email.
            *
            * If Gmail/SMTP fails, remove the generated token.
            *
            * Otherwise a failed email could leave a token
            * behind and the user could immediately hit the
            * reset-request throttle despite never receiving
            * the email.
            */
            $broker->deleteToken($customer);

            throw $exception;
        }
    }

    public function resetLinkIsValid(string $email, string $token): bool
    {
        $customer = $this->activeCustomer($email);

        if (! $customer) {
            return false;
        }

        return Password::broker('users')->tokenExists($customer, $token);
    }

    public function resetPassword(string $email, string $token, string $password): string
    {
        if (! $this->activeCustomer($email)) {
            return Password::InvalidUser;
        }

        return Password::broker('users')->reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function (User $user, string $newPassword): void {
                // Defense in depth: the public customer recovery flow must
                // never change an admin/super-admin or disabled account.
                if (! $user->isCustomer()) {
                    throw new RuntimeException('Only active customer accounts may use the storefront password reset flow.');
                }

                $user->forceFill([
                    'password' => Hash::make($newPassword),
                    'remember_token' => Str::random(60),

                    // Incrementing this security version invalidates every
                    // customer session created before this password reset.
                    // The web middleware compares the session copy with this
                    // database value on every authenticated web request.
                    'auth_session_version' => ((int) $user->auth_session_version) + 1,
                ])->save();

                event(new PasswordReset($user));

                // This notification is intentionally non-blocking. The new
                // password is already committed, so a mail-provider outage
                // must never roll back a successful security operation.
                $this->emails->passwordChanged($user);
            }
        );
    }

    private function activeCustomer(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->where('role', 'customer')
            ->where('is_active', true)
            ->first();
    }
}
