<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Support\StorefrontRedirect;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class EmailVerificationController extends Controller
{
    private const CONTINUE_URL_SESSION_KEY = 'storefront.verification.continue_url';

    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user('web');

        if ($user?->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        return view('storefront.auth.verify-email', [
            'customer' => $user,
            'expiresInMinutes' => max(5, (int) config('security.email_verification.expire_minutes', 60)),
            'seo' => [
                'title' => 'Verify Email Address | NextPlay Sportswear',
                'description' => 'Verify your NextPlay Sportswear customer email address to securely unlock your account and checkout.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        // Resolve the original safe storefront destination (for example the
        // checkout page), but do not send the customer there immediately.
        // First show a clear verification-success screen so the customer knows
        // that email ownership was confirmed successfully.
        $destination = StorefrontRedirect::intended(
            $request,
            route('account.dashboard')
        );

        $request->session()->put(self::CONTINUE_URL_SESSION_KEY, $destination);

        return redirect()
            ->route('verification.success')
            ->with('status', 'email-verified');
    }

    public function success(Request $request): View|RedirectResponse
    {
        $user = $request->user('web');

        if (! $user?->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // The value was already sanitized by StorefrontRedirect::intended()
        // when verification completed. Sanitize it again before rendering so
        // a manually modified session value can never become an open redirect.
        $continueUrl = StorefrontRedirect::sanitize(
            $request,
            $request->session()->get(self::CONTINUE_URL_SESSION_KEY)
        ) ?? route('account.dashboard');

        $path = strtolower((string) (parse_url($continueUrl, PHP_URL_PATH) ?? ''));
        $continueLabel = str_starts_with($path, '/checkout')
            ? 'Continue to Checkout'
            : ($path === '/account' || str_starts_with($path, '/account/')
                ? 'Go to My Account'
                : 'Continue');

        return view('storefront.auth.email-verified', [
            'customer' => $user,
            'continueUrl' => $continueUrl,
            'continueLabel' => $continueLabel,
            'seo' => [
                'title' => 'Email Verified | NextPlay Sportswear',
                'description' => 'Your NextPlay Sportswear customer email address has been verified successfully.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user('web');

        if ($user?->hasVerifiedEmail()) {
            return redirect()
                ->route('account.dashboard')
                ->with('status', 'Your email address is already verified.');
        }

        try {
            $user?->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'verification' => 'We could not send a verification email right now. Please try again shortly or contact support if the problem continues.',
            ]);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
