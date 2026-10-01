@props(['navigation' => []])

<nav class="np-rewards-sidebar" aria-label="My account">
    @foreach ($navigation as $item)
        @php
            $active = request()->routeIs($item['route']);
        @endphp
        <a
            href="{{ $item['href'] }}"
            class="np-rewards-sidebar__link {{ $active ? 'is-active' : '' }}"
            @if ($active) aria-current="page" @endif
        >
            <span class="np-rewards-sidebar__icon">
                <x-storefront.account.rewards.icon :name="$item['icon']" :size="25" />
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
