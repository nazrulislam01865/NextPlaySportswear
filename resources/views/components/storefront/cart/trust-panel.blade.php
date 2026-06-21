@props(['points'])

<div class="rounded-[28px] border border-blue-100 bg-blue-50 p-5 lg:p-6">
    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-blue">Included with every order</p>
    <h2 class="mt-1 font-display text-3xl font-bold uppercase text-brand-ink">Custom Order Confidence</h2>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        @foreach ($points as $point)
            <div class="flex gap-3 rounded-2xl bg-white/80 p-4 shadow-sm">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-blue text-sm font-black text-white">✓</span>
                <div>
                    <h3 class="text-sm font-black text-brand-ink">{{ $point['title'] }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $point['description'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
