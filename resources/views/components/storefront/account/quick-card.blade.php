@props([
    'title',
    'description',
    'href',
    'icon',
    'tone' => 'blue',
])

<a href="{{ $href }}" class="np-account-quick-card">
    <span class="np-account-quick-card__icon is-{{ $tone }}"><x-storefront.account.icon :name="$icon" :size="25" /></span>
    <span class="np-account-quick-card__copy">
        <strong>{{ $title }}</strong>
        <span>{{ $description }}</span>
    </span>
    <span class="np-account-quick-card__arrow"><x-storefront.account.icon name="chevron-right" :size="17" /></span>
</a>
