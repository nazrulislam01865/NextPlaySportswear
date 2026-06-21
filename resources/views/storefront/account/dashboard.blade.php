<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell
        title="My Account"
        subtitle="Your custom sportswear account center for orders, proofs, quotes, saved designs, delivery details, and support."
        :account="$account"
        :navigation="$navigation"
        :full-width="true"
    >
        <div class="space-y-6">
            <section class="overflow-hidden rounded-[30px] bg-white shadow-card ring-1 ring-slate-200">
                <div class="grid gap-0 lg:grid-cols-[1.1fr_.9fr]">
                    <div class="bg-gradient-to-br from-brand-navy via-brand-dark to-slate-950 p-6 text-white md:p-8">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="grid h-20 w-20 shrink-0 place-items-center rounded-3xl bg-white/12 font-display text-3xl font-black text-white ring-1 ring-white/15">
                                {{ $account['summary']['initials'] ?? 'NP' }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[0.24em] text-white/55">Welcome back</p>
                                <h1 class="mt-2 truncate text-3xl font-black leading-tight md:text-4xl">
                                    {{ $account['summary']['name'] }}
                                </h1>
                                <p class="mt-1 truncate text-sm font-bold text-white/70">{{ $account['summary']['email'] }}</p>
                            </div>
                        </div>

                        <p class="mt-6 max-w-2xl text-sm leading-7 text-white/75 md:text-base">
                            Manage team orders, proof approvals, saved quotes, artwork, and checkout details from one organized dashboard.
                        </p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('products.index') }}" class="btn btn-red rounded-2xl">Start New Order</a>
                            <a href="{{ route('quote.request') }}" class="btn border border-white/20 bg-white/10 text-white hover:bg-white hover:text-brand-navy rounded-2xl">Request Bulk Quote</a>
                        </div>
                    </div>

                    <div class="grid content-between gap-5 bg-slate-50 p-6 md:p-8">
                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                            @foreach ($account['stats'] as $stat)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">{{ $stat['label'] }}</p>
                                    <p class="mt-2 font-display text-3xl font-black text-brand-navy">{{ $stat['value'] }}</p>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">{{ $stat['description'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Account status</p>
                                    <p class="mt-1 text-sm font-black text-brand-ink">{{ $account['summary']['membership'] ?? 'Customer account' }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-brand-red px-3 py-1 text-xs font-black uppercase text-white">Rewards {{ $account['summary']['rewardBalance'] ?? '$0.00' }}</span>
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase text-emerald-700">Secure session</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
                <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Account center</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-brand-ink">Choose what you want to manage</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                            Quick access to the most important custom sportswear actions: order history, repeat orders, saved designs, quotes, delivery details, and support.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="btn btn-white border-brand-red text-brand-red hover:bg-brand-red hover:text-white rounded-2xl">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                            Logout
                        </button>
                    </form>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($account['cards'] as $card)
                        <x-storefront.account.action-card
                            :title="$card['title']"
                            :description="$card['description']"
                            :href="$card['href']"
                            :icon="$card['icon']"
                            :badge="$card['badge'] ?? null"
                        />
                    @endforeach
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-[1fr_380px]">
                <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
                    <div class="mb-5">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Recommended setup</p>
                        <h2 class="mt-2 text-2xl font-black text-brand-ink">Make future orders faster</h2>
                    </div>

                    <div class="grid gap-3">
                        @foreach ($account['quickSteps'] as $index => $step)
                            <div class="flex gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-brand-navy font-black text-white">{{ $index + 1 }}</span>
                                <p class="text-sm font-semibold leading-6 text-slate-600">{{ $step }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-[30px] border border-brand-red/20 bg-white p-6 shadow-card">
                    <div class="rounded-3xl bg-brand-red p-5 text-white">
                        <p class="font-display text-3xl font-black uppercase italic">Need team help?</p>
                        <p class="mt-2 text-sm font-semibold leading-6 text-white/85">
                            Send your team colors, roster, deadline, and artwork. We will prepare the right quote flow for your custom sportswear order.
                        </p>
                    </div>

                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('quote.request') }}" class="btn btn-red w-full rounded-2xl">Request Bulk Quote</a>
                        <a href="{{ route('products.index') }}" class="btn btn-white w-full rounded-2xl">Browse Products</a>
                    </div>
                </section>
            </div>
        </div>
    </x-storefront.account.shell>
</x-layouts.storefront>
