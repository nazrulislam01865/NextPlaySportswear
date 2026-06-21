@props([
    'title' => 'My Account',
    'subtitle' => 'Manage your NextPlay Sportswear customer profile, orders, quotes, and saved designs.',
    'account' => [],
    'navigation' => [],
    'fullWidth' => false,
])

<section class="bg-brand-red py-7 text-white">
    <div class="site-container text-center">
        <p class="font-display text-4xl font-black uppercase italic tracking-wide drop-shadow-lg md:text-5xl">
            {{ $title }}
        </p>
        <p class="mx-auto mt-2 max-w-2xl text-sm font-bold text-white/85 md:text-base">
            {{ $subtitle }}
        </p>
    </div>
</section>

<section class="bg-slate-50 py-8 md:py-12">
    <div class="{{ $fullWidth ? 'mx-auto' : 'site-container' }}" @if($fullWidth) style="width:min(1180px, calc(100% - 32px)); margin-inline:auto;" @endif>
        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-extrabold text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        @if (session('password_status'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-extrabold text-emerald-800 shadow-sm">
                {{ session('password_status') }}
            </div>
        @endif

        @if ($fullWidth)
            {{ $slot }}
        @else
            <div class="grid gap-6 lg:grid-cols-[300px_1fr]">
                <aside class="space-y-5">
                    <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card">
                        <div class="flex items-center gap-4">
                            <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-brand-navy font-display text-2xl font-black text-white shadow-lg shadow-brand-navy/20">
                                {{ $account['summary']['initials'] ?? 'NP' }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-lg font-black text-brand-ink">{{ $account['summary']['name'] ?? auth()->user()?->name }}</p>
                                <p class="truncate text-sm font-bold text-slate-500">{{ $account['summary']['email'] ?? auth()->user()?->email }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-slate-500">Rewards</span>
                                <span class="rounded-full bg-brand-red px-3 py-1 text-xs font-black uppercase text-white">{{ $account['summary']['rewardBalance'] ?? '$0.00' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-slate-500">Account Type</span>
                                <span class="font-black text-brand-ink">{{ $account['summary']['membership'] ?? 'Customer' }}</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="mt-5">
                            @csrf
                            <button type="submit" class="btn btn-white w-full border-brand-red text-brand-red hover:bg-brand-red hover:text-white">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <path d="M16 17l5-5-5-5"></path>
                                    <path d="M21 12H9"></path>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>

                    <nav class="rounded-[28px] border border-slate-200 bg-white p-3 shadow-card" aria-label="Account navigation">
                        @foreach ($navigation as $item)
                            @php
                                $isActive = request()->url() === $item['href'];
                            @endphp
                            <a
                                href="{{ $item['href'] }}"
                                class="mb-1 flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-black transition last:mb-0 {{ $isActive ? 'bg-brand-navy text-white shadow-md shadow-brand-navy/10' : 'text-slate-700 hover:bg-slate-50 hover:text-brand-red' }}"
                            >
                                <span>{{ $item['label'] }}</span>
                                <span aria-hidden="true">›</span>
                            </a>
                        @endforeach
                    </nav>
                </aside>

                <div class="min-w-0">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </div>
</section>
