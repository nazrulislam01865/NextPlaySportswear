<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.login', [
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
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'customer',
            'is_active' => true,
        ];

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect, or this is not an active customer account.'])
                ->onlyInput('email');
        }

        Auth::shouldUse('web');
        $request->session()->regenerate();

        return redirect()
            ->intended(route('account.dashboard'))
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
