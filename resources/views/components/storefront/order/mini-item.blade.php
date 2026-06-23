@props(['item'])

<article class="grid grid-cols-[64px_minmax(0,1fr)] gap-3 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-[80px_minmax(0,1fr)_auto]">
    <img
        src="{{ $item['image'] }}"
        alt="{{ $item['alt'] }}"
        loading="lazy"
        class="h-16 w-16 rounded-xl object-cover sm:h-20 sm:w-20"
    >
    <div class="min-w-0 flex-1">
        <h4 class="line-clamp-2 text-sm font-black text-brand-ink">{{ $item['title'] }}</h4>
        <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">
            Qty {{ $item['quantity'] }} · {{ $item['customization']['size_summary'] }}
        </p>
        <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">
            {{ $item['customization']['design_option'] }}
        </p>
    </div>
    <strong class="col-span-2 text-right text-sm text-brand-ink sm:col-span-1 sm:whitespace-nowrap">${{ number_format($item['line_total'], 2) }}</strong>
</article>
