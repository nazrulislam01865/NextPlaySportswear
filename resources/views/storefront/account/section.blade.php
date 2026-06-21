<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell
        title="{{ $section['title'] }}"
        subtitle="{{ $section['description'] }}"
        :account="$account"
        :navigation="$navigation"
    >
        <div class="space-y-6">
            <x-storefront.account.section-panel
                :title="$section['title']"
                :description="$section['description']"
            >
                <div class="rounded-[26px] border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <div class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-white text-brand-red shadow-card">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 2v20"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <h1 class="mt-5 text-2xl font-black text-brand-ink">Module-ready account section</h1>
                    <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                        This page is designed and protected now. The live data can be connected after the related database tables and backend modules are implemented.
                    </p>
                </div>

                <div class="mt-6 grid gap-4">
                    @foreach ($section['nextSteps'] as $index => $step)
                        <div class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-4">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-navy font-black text-white">{{ $index + 1 }}</span>
                            <p class="text-sm font-semibold leading-6 text-slate-600">{{ $step }}</p>
                        </div>
                    @endforeach
                </div>
            </x-storefront.account.section-panel>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('account.dashboard') }}" class="btn btn-white rounded-2xl">Back to Account</a>
                <a href="{{ route('products.index') }}" class="btn btn-red rounded-2xl">Shop Products</a>
            </div>
        </div>
    </x-storefront.account.shell>
</x-layouts.storefront>
