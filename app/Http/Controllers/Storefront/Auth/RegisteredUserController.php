<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\RegisterRequest;
use App\Services\Auth\CustomerRegistrationService;
use App\Services\Referrals\ReferralOfferService;
use App\Support\StorefrontRedirect;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly CustomerRegistrationService $registration,
        private readonly ReferralOfferService $referrals,
    ) {
    }

    public function create(Request $request): View
    {
        $offer = $this->referrals->current();
        $redirectUrl = StorefrontRedirect::capture($request);

        if (is_array($offer)) {
            $redirectUrl ??= route('products.index');

            return view('storefront.referral.register', [
                'offer' => $offer,
                'redirectUrl' => $redirectUrl,
                'seo' => [
                    'title' => 'Create Your Account | NextPlay Sportswear',
                    'description' => 'Create a NEXTPLAY account with your referral offer linked to this visit.',
                    'robots' => 'noindex, nofollow',
                ],
            ]);
        }

        $step = RegistrationEmailCodeController::step($request);
        $state = $request->session()->get(RegistrationEmailCodeController::SESSION_KEY, []);

        return view('storefront.auth.register', [
            'step' => $step,
            'otpPreviewEnabled' => RegistrationEmailCodeController::localPreviewEnabled(),
            'verifiedEmail' => RegistrationEmailCodeController::verifiedEmail($request),
            'pendingEmail' => is_array($state) ? ($state['email'] ?? '') : '',
            'codeExpiresAt' => is_array($state) ? (int) ($state['expires_at'] ?? 0) : 0,
            'resendAfter' => is_array($state) ? (int) ($state['resend_after'] ?? 0) : 0,
            'redirectUrl' => $redirectUrl,
            'seo' => [
                'title' => 'Create Customer Account | NextPlay Sportswear',
                'description' => 'Create a NextPlay Sportswear customer account for faster checkout, quote requests, team order tracking, and custom design proof updates.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $referralOffer = $this->referrals->current();
        // Referral registrations retain their separate, existing experience.
        // Normal sign-up must present a recently verified, session-bound email.
        $preverifiedEmail = RegistrationEmailCodeController::verifiedEmail($request);
        if (! is_array($referralOffer) && ($preverifiedEmail === null
            || ! hash_equals($preverifiedEmail, strtolower(trim((string) $request->validated('email')))))) {
            throw ValidationException::withMessages([
                'email' => 'Verify your email address before creating your account.',
            ]);
        }
        $referrerId = is_array($referralOffer) ? (int) ($referralOffer['referrer_id'] ?? 0) : 0;
        $user = $this->registration->register(
            $request->validated(),
            $referrerId > 0 ? $referrerId : null,
        );

        if (is_array($referralOffer)) {
            $this->referrals->attachNewCustomer($user);
        }

        if ($preverifiedEmail !== null && ! is_array($referralOffer)) {
            $user->forceFill(['email_verified_at' => now()])->saveQuietly();
            $request->session()->forget(RegistrationEmailCodeController::SESSION_KEY);

            Auth::guard('web')->login($user);
            Auth::shouldUse('web');
            $request->session()->regenerate();
            // Existing post-verification welcome listener; delivery failures
            // do not block a successfully created account.
            event(new Verified($user));

            return redirect()->route('account.dashboard')
                ->with('status', 'Your account was created successfully. Welcome to NextPlay!');
        }

        $verificationDeliveryFailed = false;
        $verificationEnabled = (bool) config('security.email_verification.enabled', false);

        if ($verificationEnabled) {
            try {
                // Laravel's Registered listener calls the user's verification
                // notification method when the model implements MustVerifyEmail.
                event(new Registered($user));
            } catch (Throwable $exception) {
                $verificationDeliveryFailed = true;
                report($exception);
            }
        }

        // Customer registration must not destroy an independent admin login
        // that may be active in another browser tab.
        Auth::guard('web')->login($user);
        Auth::shouldUse('web');
        $request->session()->regenerate();

        if (! $verificationEnabled) {
            $destination = StorefrontRedirect::intended(
                $request,
                is_array($referralOffer) ? route('products.index') : route('account.dashboard')
            );

            return redirect()
                ->to($destination)
                ->with('status', is_array($referralOffer)
                    ? 'Your account was created and your referral offer remains linked to this visit.'
                    : 'Your account was created successfully and is ready to use.');
        }

        // Preserve a safe storefront destination (for example checkout) until
        // verification succeeds. The verified middleware will enforce access.
        StorefrontRedirect::capture($request);

        $response = redirect()->route('verification.notice');

        if ($verificationDeliveryFailed) {
            $response->with(
                'verification_delivery_failed',
                'Your account was created, but the verification email could not be sent. Use the resend button below to try again.'
            );
        } else {
            $response->with('status', 'verification-link-sent');
        }

        return $response;
    }
}
