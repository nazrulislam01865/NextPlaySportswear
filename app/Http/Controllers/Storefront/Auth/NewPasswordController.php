<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\ResetPasswordRequest;
use App\Services\Auth\CustomerPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class NewPasswordController extends Controller
{
    public function __construct(
        private readonly CustomerPasswordResetService $passwordResets,
    ) {
    }

    public function create(Request $request, string $token): View|RedirectResponse
    {
        $email = Str::lower(trim((string) $request->query('email', '')));
        $token = trim($token);

        if (
            $email === ''
            || $token === ''
            || ! $this->passwordResets->resetLinkIsValid($email, $token)
        ) {
            return redirect()
                ->route('password.request')
                ->withErrors([
                    'email' => 'This password reset link is invalid or has expired. Please request a new reset link.',
                ])
                ->withInput(['email' => $email]);
        }

        return view('storefront.auth.reset-password', [
            'token' => $token,
            'email' => $email,
            'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
            'seo' => [
                'title' => 'Reset Password | NextPlay Sportswear',
                'description' => 'Choose a new password for your NextPlay Sportswear customer account.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = (string) $validated['email'];

        try {
            $status = $this->passwordResets->resetPassword(
                $email,
                (string) $validated['token'],
                (string) $validated['password'],
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors([
                    'email' => 'The password could not be reset right now. Please try again or request a new reset link.',
                ])
                ->withInput($request->only('email'));
        }

        if ($status === Password::PasswordReset) {
            return redirect()
                ->route('login')
                ->with('status', 'Your password has been reset. You can now sign in with your new password.');
        }

        $message = match ($status) {
            Password::InvalidToken => 'This password reset link is invalid or has expired. Please request a new reset link.',
            Password::InvalidUser => 'No active customer account could be reset with these details.',
            Password::ResetThrottled => 'Please wait before trying to reset the password again.',
            default => 'The password could not be reset. Please request a new reset link.',
        };

        return back()
            ->withErrors(['email' => $message])
            ->withInput($request->only('email'));
    }
}
