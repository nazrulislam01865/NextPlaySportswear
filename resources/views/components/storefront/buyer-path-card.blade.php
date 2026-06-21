@props([
    'path',
])

<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card card-hover">
    <div class="mb-4 grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-lg font-black text-brand-blue">
        {{ $path['icon'] }}
    </div>

    <h3 class="text-base font-extrabold text-brand-ink">
        {{ $path['title'] }}
    </h3>

    <p class="mt-2 text-sm text-slate-500">
        {{ $path['description'] }}
    </p>

    <a href="{{ $path['url'] }}" class="mt-4 inline-flex text-xs font-black uppercase tracking-wide text-brand-red">
        Start Your Order
    </a>
</article>
