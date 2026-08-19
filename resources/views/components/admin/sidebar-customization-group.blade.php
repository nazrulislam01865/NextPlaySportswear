@props([
    'number',
    'label',
    'active' => false,
    'hasItems' => true,
])

@if($hasItems)
    <details class="space-y-1" data-sidebar-disclosure data-sidebar-nested-disclosure @if((bool) $active) open @endif>
        <summary
            class="flex min-h-10 w-full min-w-0 cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-black text-slate-400 transition hover:bg-white/10 hover:text-white"
            data-sidebar-disclosure-toggle
            aria-label="{{ $label }}"
            data-sidebar-tooltip="{{ $label }}"
        >
            <span class="text-[10px] text-slate-500">{{ $number }}</span>
            <span class="min-w-0 flex-1 truncate" data-sidebar-label>{{ $label }}</span>
            <span class="text-[10px] transition-transform" data-sidebar-arrow>⌄</span>
        </summary>

        <div class="space-y-1 pl-4" data-sidebar-disclosure-panel>
            {{ $slot }}
        </div>
    </details>
@else
    <div
        class="flex min-h-10 w-full min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-xs font-black text-slate-400"
        aria-label="{{ $label }}"
        data-sidebar-tooltip="{{ $label }}"
    >
        <span class="text-[10px] text-slate-500">{{ $number }}</span>
        <span class="min-w-0 flex-1 truncate" data-sidebar-label>{{ $label }}</span>
    </div>
@endif
