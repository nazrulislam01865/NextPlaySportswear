@props([
    'account' => [],
    'navigation' => [],
])

<aside class="np-account-prototype-sidebar">
    <div class="np-account-prototype-sidebar__identity">
        <div class="np-account-prototype-sidebar__avatar">{{ $account['summary']['initials'] ?? 'NP' }}</div>
        <strong>{{ $account['summary']['name'] ?? auth()->user()?->name }}</strong>
        <span>{{ $account['summary']['email'] ?? auth()->user()?->email }}</span>
    </div>

    <nav class="np-account-prototype-nav" aria-label="My account navigation">
        @foreach ($navigation as $item)
            @php($active = request()->routeIs($item['route']))
            <a href="{{ $item['href'] }}" class="np-account-prototype-nav__link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                <span class="np-account-prototype-nav__icon"><x-storefront.account.icon :name="$item['icon'] ?? 'dashboard'" :size="21" /></span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <a href="{{ route('contact') }}" class="np-account-prototype-nav__link {{ request()->routeIs('contact') ? 'is-active' : '' }}" @if(request()->routeIs('contact')) aria-current="page" @endif>
            <span class="np-account-prototype-nav__icon"><x-storefront.account.icon name="support" :size="21" /></span>
            <span>Support</span>
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="np-account-prototype-sidebar__logout">
        @csrf
        <button type="submit" class="btn btn-danger-outline">
            <x-storefront.account.icon name="logout" :size="19" />
            <span>Logout</span>
        </button>
    </form>
</aside>
