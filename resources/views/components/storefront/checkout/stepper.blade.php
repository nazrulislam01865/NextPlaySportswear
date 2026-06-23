@props(['steps' => [], 'currentStep' => 'information'])

@php
    $currentIndex = collect($steps)->search(fn ($step) => $step['key'] === $currentStep);
    $currentIndex = $currentIndex === false ? count($steps) : $currentIndex;
@endphp

<div class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 py-3 shadow-sm backdrop-blur">
    <div class="site-container touch-scroll-x hide-scrollbar">
        <div class="flex min-w-max items-center gap-2 pr-3">
            @foreach ($steps as $index => $step)
                @php
                    $isActive = $step['key'] === $currentStep;
                    $isDone = $index < $currentIndex;
                @endphp
                <a href="{{ route($step['route']) }}" class="group flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-black transition sm:px-4 {{ $isActive ? 'border-brand-red bg-brand-red text-white shadow-[0_10px_20px_rgba(233,29,51,.2)]' : ($isDone ? 'border-green-200 bg-green-50 text-green-800 hover:border-green-300' : 'border-slate-200 bg-slate-50 text-slate-500 hover:text-brand-ink') }}">
                    <span class="grid h-6 w-6 place-items-center rounded-full {{ $isActive ? 'bg-white text-brand-red' : ($isDone ? 'bg-green-600 text-white' : 'bg-white text-slate-500') }}">{{ $isDone ? '✓' : $index + 1 }}</span>
                    <span>{{ $step['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
