@props(['href', 'active' => false, 'icon' => '•'])
<a href="{{ $href }}" @class([
    'mb-1 flex min-w-0 items-center gap-3 rounded-xl px-3 py-2.5 font-bold transition',
    'bg-brand-red text-white shadow-[0_8px_20px_rgba(233,29,51,.2)]' => $active,
    'text-slate-300 hover:bg-white/10 hover:text-white' => ! $active,
])>
    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-white/10 text-xs">{{ $icon }}</span>
    <span class="min-w-0 truncate">{{ $slot }}</span>
</a>
