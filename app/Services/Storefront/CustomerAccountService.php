<?php

namespace App\Services\Storefront;

use App\Models\User;
use App\Services\Referrals\ReferralOfferService;

class CustomerAccountService
{
    public function __construct(private readonly ReferralOfferService $referrals)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        return [
            'summary' => $this->summary($user),
            'stats' => $this->stats($user),
            'cards' => $this->cards($user),
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


    /**
     * Presentation data for the approved rewards prototype. No reward ledger
     * queries are made because this change is intentionally design-only.
     *
     * @return array<string, mixed>
     */
    public function rewardsPage(): array
    {
        $spent = 65.0;
        $target = 100.0;
        $rewardValue = 5.0;
        $progressPercent = (int) round(min(100, max(0, ($spent / $target) * 100)));

        return [
            'is_example' => true,
            'spent' => $spent,
            'target' => $target,
            'reward_value' => $rewardValue,
            'remaining' => max(0, $target - $spent),
            'progress_percent' => $progressPercent,
            'available_rewards' => 0.0,
            'activity' => [
                ['date' => '12 Apr 2025', 'activity' => 'Purchase #NP104235', 'progress' => 42.0, 'status' => 'Added to progress'],
                ['date' => '03 Apr 2025', 'activity' => 'Purchase #NP103892', 'progress' => 23.0, 'status' => 'Added to progress'],
            ],
        ];
    }

    /**
     * Presentation data for the approved referral prototype. The generated
     * share identifier is opaque and does not expose the customer's database ID.
     *
     * @return array<string, mixed>
     */
    public function referralsPage(User $user): array
    {
        $shareUrl = $this->referrals->shareUrl($user);
        $subject = '£5 off your first eligible NEXTPLAY order';
        $body = 'Use my NEXTPLAY referral link and get £5 off your first eligible order of £50 or more: '.$shareUrl;

        return [
            'is_example' => true,
            'share_url' => $shareUrl,
            'email_share_url' => 'mailto:?subject='.rawurlencode($subject).'&body='.rawurlencode($body),
            'steps' => [
                ['title' => 'Share your link', 'description' => 'Send your unique link to friends.'],
                ['title' => 'Friend makes first £50+ eligible purchase', 'description' => 'Your friend gets £5 off their first eligible order of £50 or more.'],
                ['title' => 'Both receive £5 rewards', 'description' => 'You get £5 off a future eligible order after their order is complete.'],
            ],
            'referrals' => [
                ['friend' => 'j.smith***@gmail.com', 'status' => 'pending', 'reward' => '£5.00 (after order completes)'],
                ['friend' => 'a.brown***@outlook.com', 'status' => 'completed', 'reward' => '£5.00 available'],
            ],
            'questions' => [
                ['question' => 'When do rewards arrive?', 'answer' => 'Rewards are added after eligible orders are completed and the returns period has passed.'],
                ['question' => 'What orders qualify?', 'answer' => 'Your friend must be a new customer and place a first eligible order of £50 or more. Exclusions may apply.'],
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, href: string, route: string, icon: string}>
     */
    public function rewardsNavigation(): array
    {
        return [
            ['label' => 'My account', 'href' => route('account.dashboard'), 'route' => 'account.dashboard', 'icon' => 'account'],
            ['label' => 'Orders', 'href' => route('account.orders.index'), 'route' => 'account.orders.*', 'icon' => 'orders'],
            ['label' => 'My Rewards', 'href' => route('account.rewards'), 'route' => 'account.rewards', 'icon' => 'rewards'],
            ['label' => 'Refer a Friend', 'href' => route('account.referrals'), 'route' => 'account.referrals', 'icon' => 'referral'],
            ['label' => 'Addresses', 'href' => route('account.addresses.index'), 'route' => 'account.addresses.*', 'icon' => 'address'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function accountNavigation(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('account.dashboard'), 'route' => 'account.dashboard'],
            ['label' => 'Profile & Security', 'href' => route('account.profile.edit'), 'route' => 'account.profile.edit'],
            ['label' => 'Order Center', 'href' => route('account.orders.dashboard'), 'route' => 'account.orders.dashboard'],
            ['label' => 'Order History', 'href' => route('account.orders.index'), 'route' => 'account.orders.index'],
            ['label' => 'My Rewards', 'href' => route('account.rewards'), 'route' => 'account.rewards'],
            ['label' => 'Refer a Friend', 'href' => route('account.referrals'), 'route' => 'account.referrals'],
            ['label' => 'Returns & Exchanges', 'href' => route('account.returns.index'), 'route' => 'account.returns.index'],
            ['label' => 'Order Downloads', 'href' => route('account.downloads.index'), 'route' => 'account.downloads.index'],
            ['label' => 'Saved Addresses', 'href' => route('account.addresses.index'), 'route' => 'account.addresses.index'],
            ['label' => 'Payment Methods', 'href' => route('account.payment-methods.index'), 'route' => 'account.payment-methods.index'],
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
     * @return array<int, array<string, string>>
     */
    private function stats(User $user): array
    {
        return [
            ['label' => 'Open Orders', 'value' => (string) $user->orders()->whereNotIn('status', ['completed', 'cancelled'])->count(), 'description' => 'Production and delivery updates'],
            ['label' => 'Saved Addresses', 'value' => (string) $user->customerAddresses()->count(), 'description' => 'Ready for faster checkout'],
            ['label' => 'Payment Methods', 'value' => (string) $user->customerPaymentMethods()->count(), 'description' => 'Tokenized only, never raw cards'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function cards(?User $user = null): array
    {
        return [
            [
                'key' => 'orders',
                'title' => 'Order History',
                'description' => 'View order status, proof updates, tracking, invoices, returns, and repeat-order options.',
                'badge' => $this->openOrderBadge($user),
                'icon' => 'orders',
                'href' => route('account.orders.index'),
            ],
            [
                'key' => 'profile',
                'title' => 'Account Settings',
                'description' => 'Edit contact, organization, sport preference, and password settings.',
                'icon' => 'settings',
                'href' => route('account.profile.edit'),
            ],
            [
                'key' => 'addresses',
                'title' => 'Saved Addresses',
                'description' => 'Save billing and shipping addresses for checkout.',
                'icon' => 'location',
                'href' => route('account.addresses.index'),
            ],
            [
                'key' => 'payment-methods',
                'title' => 'Saved Payment Methods',
                'description' => 'Manage provider-saved payment methods securely.',
                'icon' => 'payment',
                'href' => route('account.payment-methods.index'),
            ],
            [
                'key' => 'support',
                'title' => 'Support',
                'description' => 'Contact support for design, order, return, or quote help.',
                'icon' => 'support',
                'href' => route('contact'),
            ],
        ];
    }

    private function openOrderBadge(?User $user): string
    {
        if (! $user) {
            return 'Orders';
        }

        return $user->orders()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count().' open';
    }

    /**
     * @return array<int, string>
     */
    private function quickSteps(): array
    {
        return [
            'Save your preferred address so checkout can pre-fill delivery details.',
            'Use saved payment methods only through tokenized provider references; raw card data is never stored.',
            'Upload artwork during product customization or later during proof review.',
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
