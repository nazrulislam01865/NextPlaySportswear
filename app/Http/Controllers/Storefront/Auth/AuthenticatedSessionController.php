<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\LoginRequest;
use App\Models\User;
use App\Support\StorefrontRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
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
        $email = Str::lower(trim($data['email']));
        $credentials = [
            'email' => $email,
            'password' => $data['password'],
            'role' => 'customer',
            'is_active' => true,
        ];

        $suspendedCustomer = User::query()
            ->where('email', $email)
            ->where('role', 'customer')
            ->where('is_active', false)
            ->first();

        if ($suspendedCustomer && Hash::check((string) $data['password'], (string) $suspendedCustomer->password)) {
            return back()
                ->withErrors([
                    'email' => 'This customer account is currently suspended. Please contact support if you believe this is a mistake.',
                ])
                ->withInput($request->only('email', 'remember', 'redirect'));
        }

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect, or this is not an active customer account.'])
                ->withInput($request->only('email', 'remember', 'redirect'));
        }

        // Keep storefront and administrator authentication independent.
        // Signing in as a customer must not destroy an already-authenticated
        // admin guard in another tab.
        Auth::shouldUse('web');
        $request->session()->regenerate();

        $customer = Auth::guard('web')->user();
        $customer?->forceFill(['last_login_at' => now()])->saveQuietly();

        if (! $customer?->hasVerifiedEmail()) {
            // Keep the original safe destination (for example checkout) in
            // session until the signed email-verification link succeeds. Do
            // not consume it here, otherwise the verification-success page
            // would lose the customer's intended continuation target.
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
        Auth::guard('web')->logout();
        $request->session()->forget(\App\Http\Middleware\EnforceCustomerSessionVersion::SESSION_KEY);

        // Guard-safe logout: rotate the session without deleting unrelated
        // guard state (notably an active administrator session).
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'You have been signed out.');
    }
}
