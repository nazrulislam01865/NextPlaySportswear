@props([
    'seo' => [],
    'account' => [],
    'navigation' => [],
    'title' => 'Customer Orders',
    'subtitle' => 'Manage payments, production, shipments, returns, refunds, invoices, and repeat orders.',
    'eyebrow' => 'Secure order center',
])

<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell :title="$title" :subtitle="$subtitle" :account="$account" :navigation="$navigation">
        <div class="mb-6 flex flex-col justify-between gap-4 rounded-[28px] border border-slate-200 bg-white p-5 shadow-card sm:flex-row sm:items-start md:p-7">
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">{{ $eyebrow }}</p>
                <h2 class="mt-2 font-display text-3xl font-bold uppercase tracking-tight text-brand-ink md:text-4xl">{{ $title }}</h2>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-600">{{ $subtitle }}</p>
                <span class="mt-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-700">Authenticated customer</span>
            </div>
            @isset($actions)
                <div class="grid w-full gap-2 sm:flex sm:w-auto sm:shrink-0 sm:flex-wrap [&_.btn]:w-full sm:[&_.btn]:w-auto">{{ $actions }}</div>
            @endisset
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-black">Please review the information below.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        {{ $slot }}
    </x-storefront.account.shell>
</x-layouts.storefront>
