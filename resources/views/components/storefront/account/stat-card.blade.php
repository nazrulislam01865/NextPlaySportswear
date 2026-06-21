@props([
    'label',
    'value',
    'description' => '',
])

<div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
    <p class="text-sm font-black uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p class="mt-2 font-display text-4xl font-black text-brand-navy">{{ $value }}</p>
    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>
</div>
