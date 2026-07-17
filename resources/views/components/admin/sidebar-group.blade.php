@props(['label', 'icon' => '•', 'active' => false])
@php
    $tooltipLabel = trim(preg_replace('/\s+/', ' ', strip_tags((string) $label)));
@endphp
<details class="mb-1" data-sidebar-disclosure @if((bool) $active) open @endif>
    <summary
        class="flex min-h-11 w-full min-w-0 cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 text-left font-bold text-slate-300 transition hover:bg-white/10 hover:text-white"
        data-sidebar-disclosure-toggle
        aria-label="{{ $tooltipLabel }}"
        data-sidebar-tooltip="{{ $tooltipLabel }}"
    >
        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-white/10 text-xs">{{ $icon }}</span>
        <span class="min-w-0 flex-1 truncate" data-sidebar-label>{{ $label }}</span>
        <span class="text-xs transition-transform" data-sidebar-arrow>⌄</span>
    </summary>
    <div class="mt-1 space-y-1 pl-5" data-sidebar-disclosure-panel>
        {{ $slot }}
    </div>
</details>
