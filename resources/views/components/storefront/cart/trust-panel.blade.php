@props(['points'])

<div class="rounded-[24px] border border-blue-100 bg-blue-50 p-5">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-blue">Included with every order</p>
            <h2 class="mt-1 font-display text-2xl font-bold uppercase text-brand-ink sm:text-3xl">Custom Order Confidence</h2>
        </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        @foreach ($points as $point)
            <div class="flex gap-3 rounded-2xl bg-white/85 p-4 shadow-sm">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-blue text-xs font-black text-white">✓</span>
                <div>
                    <h3 class="text-sm font-black text-brand-ink">{{ $point['title'] }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $point['description'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
