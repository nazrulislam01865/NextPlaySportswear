@props([
    'title',
    'description',
    'href' => '#',
    'icon' => 'user',
    'badge' => null,
])

<a
    href="{{ $href }}"
    class="group relative flex min-h-[118px] overflow-hidden rounded-[22px] border border-slate-200 bg-white p-4 shadow-card transition duration-200 hover:-translate-y-0.5 hover:border-brand-red/45 hover:shadow-soft focus-visible:outline-brand-red md:p-5"
>
    <span class="absolute inset-y-0 left-0 w-1 bg-brand-red opacity-0 transition group-hover:opacity-100" aria-hidden="true"></span>

    <span class="flex w-full items-center gap-4">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-[#eef4fb] text-brand-navy ring-1 ring-slate-200/70 transition group-hover:bg-brand-red group-hover:text-white group-hover:ring-brand-red/20 md:h-16 md:w-16">
            @switch($icon)
                @case('orders')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h10v18H7z"/><path d="M9 7h6"/><path d="M9 11h6"/><path d="M9 15h4"/><path d="M4 7h3"/><path d="M4 11h3"/><path d="M4 15h3"/></svg>
                    @break
                @case('repeat')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    @break
                @case('settings')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 9 4.6 1.7 1.7 0 0 0 10 3V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 19.4 9c.2.6.8 1 1.6 1H21a2 2 0 1 1 0 4h-.1c-.7 0-1.3.4-1.5 1Z"/></svg>
                    @break
                @case('designs')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    @break
                @case('cart')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                    @break
                @case('mail')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/></svg>
                    @break
                @case('location')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    @break
                @case('payment')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h4"/></svg>
                    @break
                @case('support')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2-3 4"/><path d="M12 17h.01"/></svg>
                    @break
                @case('gift')
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5A2.5 2.5 0 1 1 10 4.5C10 6 12 7 12 7Z"/><path d="M12 7h4.5A2.5 2.5 0 1 0 14 4.5C14 6 12 7 12 7Z"/></svg>
                    @break
                @default
                    <svg width="29" height="29" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
            @endswitch
        </span>

        <span class="min-w-0 flex-1 pr-3">
            <span class="flex flex-wrap items-center gap-2">
                <span class="block text-base font-black leading-tight text-brand-ink transition group-hover:text-brand-red md:text-lg">{{ $title }}</span>
                @if ($badge)
                    <span class="rounded-full bg-brand-red/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-brand-red">{{ $badge }}</span>
                @endif
            </span>
            <span class="mt-1 block text-sm leading-5 text-slate-500">{{ $description }}</span>
        </span>

        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-xl font-black text-slate-400 transition group-hover:bg-brand-red group-hover:text-white" aria-hidden="true">›</span>
    </span>
</a>
