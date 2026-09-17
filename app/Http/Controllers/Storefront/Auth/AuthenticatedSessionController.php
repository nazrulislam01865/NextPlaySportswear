<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\LoginRequest;
use App\Support\StorefrontRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\Auth\CustomerSessionService;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly CustomerSessionService $sessions) {}

    public function create(Request $request): View
    {
        $redirectUrl = StorefrontRedirect::capture($request);

        return view('storefront.auth.login', [
            'redirectUrl' => $redirectUrl,
            'checkoutIntended' => str_contains((string) $redirectUrl, '/checkout'),
            'seo' => [
                'title' => 'Customer Login | NextPlay Sportswear',
                'description' => 'Sign in to your NextPlay Sportswear account to manage quotes, custom sportswear orders, design proofs, and saved checkout details.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $customer = $this->sessions->login($request, $data);

        if (! $customer->hasVerifiedEmail()) {
            StorefrontRedirect::capture($request);

            return redirect()
                ->route('verification.notice')
                ->with('status', 'Please verify your email address to unlock your customer account and checkout.');
        }

        $destination = StorefrontRedirect::intended(
            $request,
            route('account.dashboard')
        );

        return redirect()
            ->to($destination)
            ->with('status', 'Welcome back. You are signed in securely.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->sessions->logout($request);

        return redirect()
            ->route('home')
            ->with('status', 'You have been signed out.');
    }
}
