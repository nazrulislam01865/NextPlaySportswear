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
     * @return array<string, mixed>
     */
    public function section(string $section): array
    {
        $sections = collect($this->cards())->keyBy('key');
        $card = $sections->get($section, [
            'title' => 'Account Area',
            'description' => 'This account feature will be connected with the related module in a later phase.',
            'icon' => 'user',
        ]);

        return [
            'title' => $card['title'],
            'description' => $card['description'],
            'icon' => $card['icon'] ?? 'user',
            'nextSteps' => $this->moduleNextSteps($section),
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
            ['label' => 'Quotes', 'href' => route('account.section', ['section' => 'quotes']), 'route' => 'account.section'],
            ['label' => 'Saved Designs', 'href' => route('account.section', ['section' => 'saved-designs']), 'route' => 'account.section'],
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
            'rewardBalance' => '$0.00',
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
                'description' => 'View order status, proof updates, tracking, and invoices.',
                'badge' => $this->openOrderBadge($user),
                'icon' => 'orders',
                'href' => route('account.orders.index'),
            ],
            [
                'key' => 'repeat-orders',
                'title' => 'Repeat Orders',
                'description' => 'Reorder past uniforms with updated sizes and roster.',
                'badge' => 'Fast',
                'icon' => 'repeat',
                'href' => route('account.section', ['section' => 'repeat-orders']),
            ],
            [
                'key' => 'profile',
                'title' => 'Account Settings',
                'description' => 'Edit contact, organization, sport preference, and password settings.',
                'icon' => 'settings',
                'href' => route('account.profile.edit'),
            ],
            [
                'key' => 'saved-designs',
                'title' => 'Saved Designs',
                'description' => 'Access saved logos, mockups, proofs, and artwork files.',
                'icon' => 'designs',
                'href' => route('account.section', ['section' => 'saved-designs']),
            ],
            [
                'key' => 'saved-carts',
                'title' => 'Saved Carts',
                'description' => 'Return to saved product selections and quote drafts.',
                'icon' => 'cart',
                'href' => route('account.section', ['section' => 'saved-carts']),
            ],
            [
                'key' => 'quotes',
                'title' => 'Email Quotes',
                'description' => 'Check quote requests, responses, and quoted pricing.',
                'badge' => '0 saved',
                'icon' => 'mail',
                'href' => route('account.section', ['section' => 'quotes']),
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
                'description' => 'Contact support for design, order, or quote help.',
                'icon' => 'support',
                'href' => route('account.section', ['section' => 'support']),
            ],
            [
                'key' => 'gift-cards',
                'title' => 'Gift Cards',
                'description' => 'View purchased, received, or redeemed gift cards.',
                'icon' => 'gift',
                'href' => route('account.section', ['section' => 'gift-cards']),
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

    /**
     * @return array<int, string>
     */
    private function moduleNextSteps(string $section): array
    {
        return match ($section) {
            'orders' => [
                'Use Order Center to review payment, production, shipment, return, refund, invoice, and download activity.',
                'Use Order Again to rebuild a cart from an earlier order while rechecking current pricing and availability.',
            ],
            'quotes' => [
                'Connect this page with quote_requests after the quote workflow is implemented.',
                'Show quote status, admin response, quoted amount, attachments, and convert-to-order action.',
            ],
            'saved-designs' => [
                'Connect saved artwork, mockups, and proof approvals to the design proof module.',
                'Store files privately and serve customer-owned files only through signed/authorized access.',
            ],
            'saved-carts' => [
                'Connect this page to saved cart and quote draft records.',
                'Allow customers to continue unfinished carts and bulk quote drafts.',
            ],
            'support' => [
                'Connect this page with contact tickets or support messages.',
                'Include order number, quote number, and design proof references for faster support.',
            ],
            'gift-cards' => [
                'Connect this page with gift card purchases and redemption history.',
                'Display balance, status, and redemption timeline securely.',
            ],
            default => [
                'Connect this account page with its database module.',
                'Keep customer data protected with auth, ownership checks, and Form Request validation.',
            ],
        };
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
