@props(['item'])

<article class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-3">
    <img
        src="{{ $item['image'] }}"
        alt="{{ $item['alt'] }}"
        loading="lazy"
        class="h-20 w-20 shrink-0 rounded-xl object-cover"
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
    <strong class="whitespace-nowrap text-sm text-brand-ink">${{ number_format($item['line_total'], 2) }}</strong>
</article>
