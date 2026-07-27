<?php

namespace App\Services\Storefront;

use App\Models\User;

class CustomerAccountService
{
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
     * @return array<int, array<string, string>>
     */
    public function accountNavigation(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('account.dashboard'), 'route' => 'account.dashboard'],
            ['label' => 'Profile & Security', 'href' => route('account.profile.edit'), 'route' => 'account.profile.edit'],
            ['label' => 'Order Center', 'href' => route('account.orders.dashboard'), 'route' => 'account.orders.dashboard'],
            ['label' => 'Order History', 'href' => route('account.orders.index'), 'route' => 'account.orders.index'],
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
