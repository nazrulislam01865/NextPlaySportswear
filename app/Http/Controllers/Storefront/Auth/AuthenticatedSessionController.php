<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\LoginRequest;
use App\Support\StorefrontRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect, or this is not an active customer account.'])
                ->withInput($request->only('email', 'remember', 'redirect'));
        }

        // A customer login must never inherit an administrator guard or an
        // old admin intended URL from the same browser session.
        Auth::guard('admin')->logout();
        Auth::shouldUse('web');
        $request->session()->regenerate();

        $customer = Auth::guard('web')->user();
        $customer?->forceFill(['last_login_at' => now()])->saveQuietly();

        $destination = StorefrontRedirect::intended($request, route('account.dashboard'));

        return redirect()
            ->to($destination)
            ->with('status', 'Welcome back. You are signed in securely.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'You have been signed out.');
    }
}
