@props(['title'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-slate-50 p-4']) }}>
    <h3 class="text-sm font-black text-brand-ink">{{ $title }}</h3>
    <div class="mt-2 text-sm font-semibold leading-6 text-slate-600">
        {{ $slot }}
    </div>
</div>
