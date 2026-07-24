<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('admin')->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'The administrator credentials are incorrect.']);
        }

        $admin = Auth::guard('admin')->user();
        if (! $admin?->isAdmin()) {
            Auth::guard('admin')->logout();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => 'This account does not have administrator access.']);
        }

        AdminRbac::syncDefaults(false);

        // An administrator session must never double as a customer session.
        Auth::guard('web')->logout();
        Auth::shouldUse('admin');
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->saveQuietly();

        $ip = (string) ($request->ip() ?: 'unknown');
        $emailFingerprint = hash('sha256', substr(strtolower(trim((string) $credentials['email'])), 0, 190));
        RateLimiter::clear('admin-login-account:'.$ip.':'.$emailFingerprint);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'You have been signed out of the admin panel.');
    }
}
