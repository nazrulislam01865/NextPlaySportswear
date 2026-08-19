@props([
    'types',
    'routeName',
    'isActive' => false,
    'activeType' => null,
])

@foreach($types as $customizationType)
    <a
        href="{{ route($routeName, $customizationType->value) }}"
        aria-label="{{ $customizationType->label() }}"
        data-sidebar-tooltip="{{ $customizationType->label() }}"
        @if($isActive && $activeType === $customizationType->value) data-sidebar-active="true" @endif
        @class([
            'flex min-h-9 min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-bold transition',
            'bg-brand-red text-white' => $isActive && $activeType === $customizationType->value,
            'text-slate-400 hover:bg-white/10 hover:text-white' => ! ($isActive && $activeType === $customizationType->value),
        ])
    >
        <span class="shrink-0 text-[10px] opacity-80">{{ $customizationType->menuNumber() }}</span>
        <span class="min-w-0 truncate" data-sidebar-label>{{ $customizationType->label() }}</span>
    </a>
@endforeach
