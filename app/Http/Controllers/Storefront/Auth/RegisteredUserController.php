<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\RegisterRequest;
use App\Services\Auth\CustomerRegistrationService;
use App\Services\Referrals\ReferralOfferService;
use App\Support\StorefrontRedirect;
use Illuminate\Auth\Events\Verified;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
            $state = $request->session()->get(RegistrationEmailCodeController::SESSION_KEY, []);

            return view('storefront.referral.register', [
                'offer' => $offer,
                'step' => RegistrationEmailCodeController::step($request),
                'verifiedEmail' => RegistrationEmailCodeController::verifiedEmail($request),
                'pendingEmail' => is_array($state) ? (string) ($state['email'] ?? '') : '',
                'resendAfter' => is_array($state) ? (int) ($state['resend_after'] ?? 0) : 0,
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
        // Every new customer, including a referral customer, uses the SAME
        // session-bound email code. Successful email ownership verification
        // must never lead into a second, legacy email-link verification.
        $preverifiedEmail = RegistrationEmailCodeController::verifiedEmail($request);
        if ($preverifiedEmail === null || ! hash_equals(
            $preverifiedEmail,
            strtolower(trim((string) $request->validated('email')))
        )) {
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

        // Persist verification at registration time for BOTH signup variants.
        // The OTP has already proved ownership and the session-bound email
        // matches the newly stored customer email.
        $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        $request->session()->forget(RegistrationEmailCodeController::SESSION_KEY);

        Auth::guard('web')->login($user);
        Auth::shouldUse('web');
        $request->session()->regenerate();
        // Reuse the welcome listener; this must not trigger a second
        // verification email or affect the active referral session.
        event(new Verified($user));

        $destination = StorefrontRedirect::intended(
            $request,
            is_array($referralOffer) ? route('products.index') : route('account.dashboard')
        );

        return redirect()->to($destination)->with('status',
            is_array($referralOffer)
                ? 'Your account is verified and your referral offer remains linked to this visit.'
                : 'Your account was created successfully. Welcome to NextPlay!'
        );
    }
}
