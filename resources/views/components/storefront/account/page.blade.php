@props([
    'seo' => [],
    'title',
    'subtitle',
    'account' => [],
    'navigation' => [],
    'contentOnly' => false,
])

<x-layouts.storefront :seo="$seo">
    <x-storefront.account.frame :title="$title" :subtitle="$subtitle" :account="$account" :navigation="$navigation" :content-only="$contentOnly">
        {{ $slot }}
    </x-storefront.account.frame>
</x-layouts.storefront>
