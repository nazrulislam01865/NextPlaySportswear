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

    public function section(Request $request, string $section): View
    {
        abort_unless($this->allowedSection($section), 404);

        return view('storefront.account.section', [
            'seo' => $this->seo('My Account - ' . str($section)->headline() . ' | NextPlay Sportswear'),
            'account' => $this->accountService->dashboard($request->user()),
            'navigation' => $this->accountService->accountNavigation(),
            'section' => $this->accountService->section($section),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function seo(string $title): array
    {
        return [
            'title' => $title,
            'description' => 'Manage your NextPlay Sportswear customer profile, quotes, orders, saved designs, and proof approvals.',
            'robots' => 'noindex, nofollow',
        ];
    }

    private function allowedSection(string $section): bool
    {
        return in_array($section, [
            'orders',
            'repeat-orders',
            'saved-designs',
            'saved-carts',
            'quotes',
            'addresses',
            'payment-methods',
            'support',
            'gift-cards',
        ], true);
    }
}
