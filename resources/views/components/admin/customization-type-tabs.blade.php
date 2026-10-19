@props([
    'heading',
    'ariaLabel',
    'links',
    'activeType',
    'routeName',
    'sizeOptionLink' => null,
])

<div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
    <div class="border-b border-slate-100 p-4 sm:p-5">
        <p class="text-xs font-black uppercase tracking-[.22em] text-slate-400">
            {{ $heading }}
        </p>
    </div>
    <div class="flex flex-wrap items-stretch gap-2 p-4 sm:p-5" aria-label="{{ $ariaLabel }}">
        @foreach($links as $link)
            <a
                href="{{ route($routeName, $link['type']->value) }}"
                @class([
                    'inline-flex min-h-11 max-w-full items-center justify-center gap-2 rounded-2xl border px-4 text-center text-sm font-black transition',
                    'border-brand-blue bg-brand-blue text-white shadow-sm' => $activeType === $link['type'],
                    'border-slate-200 bg-white text-slate-600 hover:border-brand-blue hover:text-brand-blue' => $activeType !== $link['type'],
                ])
            >
                <span>{{ $link['number'] }}</span>
                <span class="min-w-0 break-words">{{ $link['label'] }}</span>
            </a>
        @endforeach

        @if($sizeOptionLink)
            <a
                href="{{ $sizeOptionLink['url'] }}"
                class="inline-flex min-h-11 max-w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-center text-sm font-black text-slate-600 transition hover:border-brand-blue hover:text-brand-blue"
            >
                <span>{{ $sizeOptionLink['number'] }}</span>
                <span class="min-w-0 break-words">{{ $sizeOptionLink['label'] }}</span>
            </a>
        @endif
    </div>
</div>
