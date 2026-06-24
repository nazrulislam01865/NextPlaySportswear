@props(['label', 'icon' => '•', 'active' => false])
<div class="mb-1" x-data="{ open: @js((bool) $active) }">
    <button
        type="button"
        class="flex min-h-11 w-full min-w-0 items-center gap-3 rounded-xl px-3 py-2.5 text-left font-bold transition"
        :class="open || @js((bool) $active) ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        @click="open = ! open"
        :aria-expanded="open.toString()"
    >
        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-white/10 text-xs">{{ $icon }}</span>
        <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
        <span class="text-xs transition-transform" :class="open ? 'rotate-180' : ''">⌄</span>
    </button>
    <div x-cloak x-show="open" class="mt-1 space-y-1 pl-5">
        {{ $slot }}
    </div>
</div>
