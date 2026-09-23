<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\RegisterRequest;
use App\Services\Auth\CustomerRegistrationService;
use App\Support\StorefrontRedirect;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly CustomerRegistrationService $registration,
    ) {
    }

    public function create(Request $request): View
    {
        return view('storefront.auth.register', [
            'redirectUrl' => StorefrontRedirect::capture($request),
            'seo' => [
                'title' => 'Create Customer Account | NextPlay Sportswear',
                'description' => 'Create a NextPlay Sportswear customer account for faster checkout, quote requests, team order tracking, and custom design proof updates.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registration->register($request->validated());
        $verificationDeliveryFailed = false;

        try {
            // Laravel's Registered listener calls the user's verification
            // notification method when the model implements MustVerifyEmail.
            event(new Registered($user));
        } catch (Throwable $exception) {
            $verificationDeliveryFailed = true;
            report($exception);
        }

        // Customer registration must not destroy an independent admin login
        // that may be active in another browser tab.
        Auth::guard('web')->login($user);
        Auth::shouldUse('web');
        $request->session()->regenerate();

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
