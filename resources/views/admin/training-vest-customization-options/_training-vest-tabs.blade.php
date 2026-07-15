<div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
    <div class="border-b border-slate-100 p-4 sm:p-5">
        <p class="text-xs font-black uppercase tracking-[.22em] text-slate-400">
            {{ $type->groupNumber() }} {{ $type->groupLabel() }} Submenus
        </p>
    </div>
    <div class="flex gap-2 overflow-x-auto p-4 sm:p-5" aria-label="{{ $type->groupLabel() }} type navigation">
        @foreach($typeLinks as $link)
            <a
                href="{{ route('admin.training-vest-customization-options.type', $link['type']->value) }}"
                @class([
                    'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-2xl border px-4 text-sm font-black transition',
                    'border-brand-blue bg-brand-blue text-white shadow-sm' => $type === $link['type'],
                    'border-slate-200 bg-white text-slate-600 hover:border-brand-blue hover:text-brand-blue' => $type !== $link['type'],
                ])
            >
                <span>{{ $link['number'] }}</span>
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
