<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Referrals\ReferralOfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReferralOfferController extends Controller
{
    public function __construct(private readonly ReferralOfferService $referrals)
    {
    }

    public function show(Request $request, string $token): View|RedirectResponse
    {
        $offer = $this->referrals->activate($token);

        if (! is_array($offer)) {
            return redirect()
                ->route('home')
                ->withErrors(['referral' => 'This referral link is invalid or is not available for this account.']);
        }

        return view('storefront.referral.offer', [
            'offer' => $offer,
            'seo' => [
                'title' => 'Your £'.number_format((float) ($offer['reward_amount'] ?? 0), 0).' Referral Offer | NextPlay Sportswear',
                'description' => 'Your NEXTPLAY referral offer is linked to this visit and will be applied when an eligible first order reaches the required spend.',
                'robots' => 'noindex, nofollow',
            ],
        ]);
    }
}
