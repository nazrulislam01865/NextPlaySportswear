<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\ForgotPasswordRequest;
use App\Services\Auth\CustomerPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly CustomerPasswordResetService $passwordResets,
    ) {
    }

    public function create(): View
    {
        return view('storefront.auth.forgot-password', [
            'seo' => [
                'title' => 'Forgot Password | NextPlay Sportswear',
                'description' => 'Request a secure password reset link for your NextPlay Sportswear customer account.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');
        $expiresInMinutes = max(
            1,
            (int) config('auth.passwords.users.expire', 15)
        );

        $genericStatus = sprintf(
            'If an active customer account matches that email, a password reset link will be sent. The link expires in %d minutes and can only be used once.',
            $expiresInMinutes,
        );

        try {
            $status = $this->passwordResets->sendResetLink($email);
        } catch (Throwable $exception) {
            /*
             * Keep the public response indistinguishable from an unknown
             * account. The exception is still reported for operations, and
             * CustomerPasswordResetService removes any token created before a
             * provider/queue failure.
             */
            report($exception);

            return back()
                ->with('status', $genericStatus)
                ->withInput($request->only('email'));
        }

        /*
         * InvalidUser, ResetLinkSent and ResetThrottled deliberately return
         * the same response. This prevents the public recovery endpoint from
         * confirming whether a customer email exists or was recently used.
         */
        if (in_array($status, [
            Password::InvalidUser,
            Password::ResetLinkSent,
            Password::ResetThrottled,
        ], true)) {
            return back()
                ->with('status', $genericStatus)
                ->withInput($request->only('email'));
        }

        report(new \RuntimeException(
            'Unexpected password-reset broker status: '.(string) $status
        ));

        return back()
            ->with('status', $genericStatus)
            ->withInput($request->only('email'));
    }
}
