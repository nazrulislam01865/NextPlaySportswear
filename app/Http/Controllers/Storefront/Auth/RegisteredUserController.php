<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.register', [
            'seo' => [
                'title' => 'Create Customer Account | NextPlay Sportswear',
                'description' => 'Create a NextPlay Sportswear customer account for faster checkout, quote requests, team order tracking, and custom design proof updates.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => trim(strip_tags($data['name'])),
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('account.dashboard')
            ->with('status', 'Your account has been created. You can now manage quotes, orders, and custom design proofs.');
    }
}
