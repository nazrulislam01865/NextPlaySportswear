@props([
    'seo' => [],
    'account' => [],
    'navigation' => [],
    'title' => 'Customer Orders',
    'subtitle' => 'Manage payments, production, shipments, returns, refunds, invoices, and repeat orders.',
    'eyebrow' => 'Secure order center',
])

<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell :title="$title" :subtitle="$subtitle" :account="$account" :navigation="$navigation">
        <div class="np-account-order-tools">
            <span class="np-account-order-tools__label">{{ $eyebrow }}</span>
            @isset($actions)
                <div class="np-account-order-tools__actions">{{ $actions }}</div>
            @endisset
        </div>

        {{ $slot }}
    </x-storefront.account.shell>
</x-layouts.storefront>
