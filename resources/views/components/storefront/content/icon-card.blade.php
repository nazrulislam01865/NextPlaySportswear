@props([
    'icon' => '✓',
    'title',
    'description',
])

<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-1 hover:shadow-soft">
    <div class="grid h-11 w-11 place-items-center rounded-xl bg-red-50 font-black text-brand-red">{{ $icon }}</div>
    <h3 class="mt-4 text-base font-extrabold text-brand-ink">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
    {{ $slot }}
</article>
