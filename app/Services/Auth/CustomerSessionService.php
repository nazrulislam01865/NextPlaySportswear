<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CustomerSessionService
{
    public function login(Request $request, array $data): User
    {
        $email = Str::lower(trim((string) $data['email']));
        $suspended = User::query()
            ->where('email', $email)
            ->where('role', 'customer')
            ->where('is_active', false)
            ->first();

        if ($suspended && Hash::check((string) $data['password'], (string) $suspended->password)) {
            throw ValidationException::withMessages([
                'email' => 'This customer account is currently suspended. Please contact support if you believe this is a mistake.',
            ]);
        }

        $credentials = [
            'email' => $email,
            'password' => (string) $data['password'],
            'role' => 'customer',
            'is_active' => true,
        ];

        if (! Auth::guard('web')->attempt($credentials, (bool) ($data['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect, or this is not an active customer account.',
            ]);
        }

        $customer = Auth::guard('web')->user();
        if (! $customer instanceof User || ! $customer->isCustomer()) {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect, or this is not an active customer account.',
            ]);
        }

        $this->finalize($request, $customer, true);

        return $customer;
    }

    public function start(Request $request, User $customer, bool $markLastLogin = false): void
    {
        Auth::guard('admin')->logout();
        Auth::guard('web')->login($customer);
        $this->finalize($request, $customer, $markLastLogin);
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function finalize(Request $request, User $customer, bool $markLastLogin): void
    {
        Auth::guard('admin')->logout();
        Auth::shouldUse('web');
        $request->session()->regenerate();

        if ($markLastLogin) {
            $customer->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }
}
