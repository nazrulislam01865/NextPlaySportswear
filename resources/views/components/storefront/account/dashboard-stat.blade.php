@props([
    'icon',
    'value',
    'label',
    'tone' => 'blue',
    'href' => null,
])

@if($href)
    <a href="{{ $href }}" class="np-account-dashboard-stat is-link">
        <span class="np-account-dashboard-stat__icon is-{{ $tone }}"><x-storefront.account.icon :name="$icon" :size="22" /></span>
        <span class="np-account-dashboard-stat__copy">
            <strong>{{ $value }}</strong>
            <span>{{ $label }}</span>
        </span>
        <span class="np-account-dashboard-stat__arrow"><x-storefront.account.icon name="chevron-right" :size="18" /></span>
    </a>
@else
    <div class="np-account-dashboard-stat">
        <span class="np-account-dashboard-stat__icon is-{{ $tone }}"><x-storefront.account.icon :name="$icon" :size="22" /></span>
        <span class="np-account-dashboard-stat__copy">
            <strong>{{ $value }}</strong>
            <span>{{ $label }}</span>
        </span>
    </div>
@endif
