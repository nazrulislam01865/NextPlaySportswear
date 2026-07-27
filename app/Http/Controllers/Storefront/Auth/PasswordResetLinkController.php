<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.forgot-password', [
            'seo' => [
                'title' => 'Forgot Password | NextPlay Sportswear',
                'description' => 'Request a secure password reset link for your NextPlay Sportswear customer account.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $genericStatus = 'If an active customer account matches that email, a password reset link has been sent.';

        // Keep the customer and admin recovery flows isolated. Return the same
        // response for unknown addresses so this endpoint cannot enumerate users.
        $isActiveCustomer = User::query()
            ->where('email', $email)
            ->where('role', 'customer')
            ->where('is_active', true)
            ->exists();

        if (! $isActiveCustomer) {
            return back()->with('status', $genericStatus);
        }

        try {
            $status = Password::broker('users')->sendResetLink([
                'email' => $email,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['email' => 'The reset email could not be sent right now. Please try again shortly.'])
                ->withInput($request->only('email'));
        }

        if ($status === Password::ResetThrottled) {
            return back()
                ->withErrors(['email' => 'Please wait before requesting another password reset email.'])
                ->withInput($request->only('email'));
        }

        return back()->with('status', $genericStatus);
    }
}
