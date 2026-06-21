@props(['title', 'description' => null, 'action' => null])

<section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-card">
    <div class="flex flex-col gap-4 border-b border-slate-100 bg-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-7">
        <div>
            <h2 class="font-display text-3xl font-bold uppercase text-brand-ink">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $description }}</p>
            @endif
        </div>
        @if ($action)
            {{ $action }}
        @endif
    </div>
    <div class="p-5 lg:p-7">
        {{ $slot }}
    </div>
</section>
