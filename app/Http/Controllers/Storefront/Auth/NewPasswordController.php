<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('storefront.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'seo' => [
                'title' => 'Reset Password | NextPlay Sportswear',
                'description' => 'Choose a new password for your NextPlay Sportswear customer account.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->letters()->numbers(),
            ],
        ]);

        $email = Str::lower(trim((string) $validated['email']));

        $isActiveCustomer = User::query()
            ->where('email', $email)
            ->where('role', 'customer')
            ->where('is_active', true)
            ->exists();

        if (! $isActiveCustomer) {
            return back()
                ->withErrors(['email' => 'No active customer account could be reset with these details.'])
                ->withInput($request->only('email'));
        }

        $status = Password::broker('users')->reset(
            [
                'email' => $email,
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            return redirect()
                ->route('login')
                ->with('status', 'Your password has been reset. You can now sign in with your new password.');
        }

        $message = match ($status) {
            Password::InvalidToken => 'This password reset link is invalid or has expired.',
            Password::InvalidUser => 'No active customer account could be reset with these details.',
            Password::ResetThrottled => 'Please wait before trying to reset the password again.',
            default => 'The password could not be reset. Please request a new reset link.',
        };

        return back()
            ->withErrors(['email' => $message])
            ->withInput($request->only('email'));
    }
}
