@props([
    'seo' => [],
    'title',
    'subtitle',
    'account' => [],
    'navigation' => [],
])

<x-layouts.storefront :seo="$seo">
    <x-storefront.account.frame :title="$title" :subtitle="$subtitle" :account="$account" :navigation="$navigation">
        {{ $slot }}
    </x-storefront.account.frame>
</x-layouts.storefront>
