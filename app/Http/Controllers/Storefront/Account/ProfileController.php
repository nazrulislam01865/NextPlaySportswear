<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\UpdatePasswordRequest;
use App\Http\Requests\Storefront\Account\UpdateProfileRequest;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly CustomerAccountService $accountService)
    {
    }

    public function edit(Request $request): View
    {
        return view('storefront.account.profile', [
            'seo' => [
                'title' => 'Profile & Security | NextPlay Sportswear',
                'description' => 'Update your customer profile and password securely.',
                'robots' => 'noindex, nofollow',
            ],
            'account' => $this->accountService->dashboard($request->user()),
            'navigation' => $this->accountService->accountNavigation(),
            'options' => $this->accountService->profileOptions(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $this->clean($data['name']),
            'email' => Str::lower($data['email']),
            'phone' => $this->clean($data['phone'] ?? ''),
            'company_name' => $this->clean($data['company_name'] ?? ''),
            'preferred_sport' => $data['preferred_sport'] ?? null,
            'marketing_consent' => $request->boolean('marketing_consent'),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'Your profile has been updated securely.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        $request->session()->regenerate();

        return back()->with('password_status', 'Your password has been updated.');
    }

    private function clean(?string $value): ?string
    {
        $cleaned = trim(strip_tags((string) $value));

        return $cleaned === '' ? null : $cleaned;
    }
}
