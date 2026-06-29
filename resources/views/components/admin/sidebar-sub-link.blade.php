@props(['href', 'active' => false])
<a href="{{ $href }}" @if($active) data-sidebar-active="true" @endif @class([
    'flex min-h-10 min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold transition',
    'bg-brand-red text-white' => $active,
    'text-slate-400 hover:bg-white/10 hover:text-white' => ! $active,
])>
    <span class="text-[10px]">•</span>
    <span class="min-w-0 truncate">{{ $slot }}</span>
</a>
