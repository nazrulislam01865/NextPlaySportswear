<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Services\Storefront\CustomerAccountService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private readonly CustomerAccountService $accountService)
    {
    }

    public function index(Request $request): View
    {
        return view('storefront.account.dashboard', [
            'seo' => $this->seo('My Account | NextPlay Sportswear'),
            'account' => $this->accountService->dashboard($request->user()),
            'navigation' => $this->accountService->accountNavigation(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function seo(string $title): array
    {
        return [
            'title' => $title,
            'description' => 'Manage your NextPlay Sportswear profile, orders, delivery details, returns, downloads, and support.',
            'robots' => 'noindex, nofollow',
        ];
    }
}
