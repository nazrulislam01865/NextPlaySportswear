@props([
    'title' => 'My Account',
    'subtitle' => 'Manage your NextPlay Sportswear customer profile, orders, quotes, and saved designs.',
    'account' => [],
    'navigation' => [],
    'fullWidth' => false,
])

<x-storefront.account.frame :title="$title" :subtitle="$subtitle" :account="$account" :navigation="$navigation" :full-width="$fullWidth">
    {{ $slot }}
</x-storefront.account.frame>
