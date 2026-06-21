@props([
    'title',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7']) }}>
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-black text-brand-ink">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>
            @endif
        </div>
    </div>

    {{ $slot }}
</section>
