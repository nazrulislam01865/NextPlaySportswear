<?php

namespace App\Services\Storefront;

use App\Models\User;
use App\Services\Referrals\ReferralOfferService;
use App\Services\Rewards\RewardService;

class CustomerAccountService
{
    public function __construct(
        private readonly ReferralOfferService $referrals,
        private readonly RewardService $rewards,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        $stats = $this->stats($user);

        return [
            'summary' => $this->summary($user),
            'stats' => $stats,
            'cards' => $this->cards(),
            'quickSteps' => $this->quickSteps(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileOptions(): array
    {
        return [
            'sports' => [
                'basketball' => 'Basketball',
                'baseball' => 'Baseball',
                'football' => 'Football',
                'soccer' => 'Soccer',
                'volleyball' => 'Volleyball',
                'hockey' => 'Hockey',
                'cheerleading' => 'Cheerleading',
                'training' => 'Training / gym wear',
                'other' => 'Other / multiple sports',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addressBook(User $user): array
    {
        $addresses = $user->customerAddresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return [
            'addresses' => $addresses,
            'total' => $addresses->count(),
            'default' => $addresses->firstWhere('is_default', true),
            'types' => [
                'shipping' => 'Shipping Address',
                'billing' => 'Billing Address',
                'both' => 'Billing & Shipping',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentWallet(User $user): array
    {
        $paymentMethods = $user->customerPaymentMethods()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return [
            'paymentMethods' => $paymentMethods,
            'total' => $paymentMethods->count(),
            'default' => $paymentMethods->firstWhere('is_default', true),
            'expiryYears' => range((int) now()->format('Y'), (int) now()->format('Y') + 15),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function usStates(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
            'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
            'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
            'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
            'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
            'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
            'DC' => 'District of Columbia',
        ];
    }


    /** @return array<string, mixed> */
    public function sidebarData(User $user): array
    {
        return [
            'summary' => $this->summary($user),
        ];
    }

    /** @return array<string, mixed> */
    public function rewardsPage(User $user): array
    {
        $data = $this->rewards->pageData($user);
        $data['referral'] = $this->referrals->settings();

        return $data;
    }

    /** @return array<string, mixed> */
    public function referralsPage(User $user): array
    {
        $shareUrl = $this->referrals->shareUrl($user);
        $settings = $this->referrals->settings();
        $friendReward = (float) $settings['friend_reward_amount'];
        $referrerReward = (float) $settings['referrer_reward_amount'];
        $minimumOrder = (float) $settings['minimum_order'];
        $subject = '£'.number_format($friendReward, 0).' off your first eligible NEXTPLAY order';
        $body = 'Use my NEXTPLAY referral link and get £'.number_format($friendReward, 2).' off your first eligible order of £'.number_format($minimumOrder, 2).' or more: '.$shareUrl;

        $referrals = $user->referralsMade()
            ->with(['referredUser:id,email', 'order:id,order_number,status,customer_email'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function ($referral): array {
                $email = (string) ($referral->referredUser?->email ?? $referral->order?->customer_email ?? 'Friend');
                return [
                    'friend' => $this->maskEmail($email),
                    'status' => $referral->status,
                    'reward' => $referral->rewarded_at
                        ? '£'.number_format((float) $referral->referrer_reward_amount, 2).' available'
                        : ($referral->status === 'cancelled'
                            ? 'Not earned — order cancelled'
                            : '£'.number_format((float) $referral->referrer_reward_amount, 2).' (after paid order completes)'),
                ];
            })
            ->all();

        return [
            'is_example' => false,
            'enabled' => (bool) $settings['enabled'],
            'share_url' => $shareUrl,
            'email_share_url' => 'mailto:?subject='.rawurlencode($subject).'&body='.rawurlencode($body),
            'friend_reward_amount' => $friendReward,
            'referrer_reward_amount' => $referrerReward,
            'minimum_order' => $minimumOrder,
            'steps' => [
                ['title' => 'Share your link', 'description' => 'Send your unique link to friends.'],
                ['title' => 'Friend makes first £'.number_format($minimumOrder, 0).'+ eligible purchase', 'description' => 'Your friend gets £'.number_format($friendReward, 0).' off their first eligible order of £'.number_format($minimumOrder, 0).' or more.'],
                ['title' => 'You receive your referral reward', 'description' => 'You get £'.number_format($referrerReward, 0).' to use on a future order after their order is completed.'],
            ],
            'referrals' => $referrals,
            'questions' => [
                ['question' => 'When do rewards arrive?', 'answer' => 'Referral rewards are added after the referred order is marked completed.'],
                ['question' => 'What orders qualify?', 'answer' => 'Your friend must create a new customer account from your referral and use it once on their first eligible order of £'.number_format($minimumOrder, 0).' or more. Existing customers cannot redeem a referral.'],
            ],
        ];
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.'***@'.$domain;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function accountNavigation(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('account.dashboard'), 'route' => 'account.dashboard', 'icon' => 'dashboard'],
            ['label' => 'Profile & Security', 'href' => route('account.profile.edit'), 'route' => 'account.profile.edit', 'icon' => 'profile'],
            ['label' => 'Order Center', 'href' => route('account.orders.dashboard'), 'route' => 'account.orders.dashboard', 'icon' => 'order-center'],
            ['label' => 'Order History', 'href' => route('account.orders.index'), 'route' => 'account.orders.index', 'icon' => 'order-history'],
            ['label' => 'My Rewards', 'href' => route('account.rewards'), 'route' => 'account.rewards', 'icon' => 'rewards'],
            ['label' => 'Refer a Friend', 'href' => route('account.referrals'), 'route' => 'account.referrals', 'icon' => 'referral'],
            ['label' => 'Returns & Exchanges', 'href' => route('account.returns.index'), 'route' => 'account.returns.index', 'icon' => 'returns'],
            ['label' => 'Order Downloads', 'href' => route('account.downloads.index'), 'route' => 'account.downloads.index', 'icon' => 'downloads'],
            ['label' => 'Saved Addresses', 'href' => route('account.addresses.index'), 'route' => 'account.addresses.index', 'icon' => 'address'],
            ['label' => 'Payment Methods', 'href' => route('account.payment-methods.index'), 'route' => 'account.payment-methods.index', 'icon' => 'payment'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'initials' => $this->initials($user->name),
            'membership' => 'Customer account',
            'joined' => optional($user->created_at)->format('M d, Y'),
        ];
    }

    /**
     * @return array<string, int|float|string>
     */
    private function stats(User $user): array
    {
        $orders = $user->orders()
            ->reorder()
            ->selectRaw("COUNT(*) as total_orders")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as open_orders")
            ->selectRaw("SUM(CASE WHEN payment_status IN ('pending', 'failed', 'processing') THEN 1 ELSE 0 END) as awaiting_payment")
            ->first();

        $rewardBalance = max(0, $this->rewards->availableBalance($user));

        return [
            'total_orders' => (int) ($orders?->total_orders ?? 0),
            'open_orders' => (int) ($orders?->open_orders ?? 0),
            'awaiting_payment' => (int) ($orders?->awaiting_payment ?? 0),
            'saved_addresses' => $user->customerAddresses()->count(),
            'payment_methods' => $user->customerPaymentMethods()->count(),
            'reward_balance' => $rewardBalance,
            'reward_balance_display' => '£'.number_format($rewardBalance, 2),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function cards(): array
    {
        return [
            [
                'key' => 'orders',
                'title' => 'Order History',
                'description' => 'View past orders, track status, and reorder easily.',
                'icon' => 'order-history',
                'tone' => 'blue',
                'href' => route('account.orders.index'),
            ],
            [
                'key' => 'order-center',
                'title' => 'Order Center',
                'description' => 'Create and manage new orders from start to finish.',
                'icon' => 'order-center',
                'tone' => 'orange',
                'href' => route('account.orders.dashboard'),
            ],
            [
                'key' => 'addresses',
                'title' => 'Saved Addresses',
                'description' => 'Manage your billing and shipping addresses.',
                'icon' => 'address',
                'tone' => 'blue',
                'href' => route('account.addresses.index'),
            ],
            [
                'key' => 'payment-methods',
                'title' => 'Payment Methods',
                'description' => 'Manage saved payment methods securely.',
                'icon' => 'payment',
                'tone' => 'green',
                'href' => route('account.payment-methods.index'),
            ],
            [
                'key' => 'profile',
                'title' => 'Profile & Security',
                'description' => 'Update your profile, organization settings and password.',
                'icon' => 'profile',
                'tone' => 'purple',
                'href' => route('account.profile.edit'),
            ],
            [
                'key' => 'support',
                'title' => 'Support',
                'description' => 'Get help with orders, returns, or quote requests.',
                'icon' => 'support',
                'tone' => 'slate',
                'href' => route('contact'),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function quickSteps(): array
    {
        return [
            'Save your preferred address for faster checkout.',
            'Add a payment method for secure and easy payments.',
            'Keep your profile information up to date.',
        ];
    }


    private function initials(?string $name): string
    {
        $parts = collect(explode(' ', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)));

        return $parts->implode('') ?: 'NP';
    }
}
