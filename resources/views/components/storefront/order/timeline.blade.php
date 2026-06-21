@props(['timeline'])

<div class="relative grid gap-0">
    @foreach ($timeline as $step)
        @php
            $state = $step['state'] ?? 'pending';
            $dotClass = match ($state) {
                'done' => 'bg-green-600 text-white border-green-600',
                'current' => 'bg-brand-red text-white border-brand-red',
                default => 'bg-white text-slate-500 border-slate-300',
            };
        @endphp
        <div class="relative grid grid-cols-[42px_1fr] gap-4 pb-7 last:pb-0">
            @if (! $loop->last)
                <span class="absolute left-[20px] top-10 h-[calc(100%-38px)] w-px bg-slate-200"></span>
            @endif
            <span class="relative z-10 grid h-10 w-10 place-items-center rounded-full border-2 text-sm font-black {{ $dotClass }}">
                {{ $state === 'done' ? '✓' : $loop->iteration }}
            </span>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <h3 class="text-base font-black text-brand-ink">{{ $step['title'] }}</h3>
                    <span class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $step['time'] }}</span>
                </div>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $step['description'] }}</p>
            </div>
        </div>
    @endforeach
</div>
