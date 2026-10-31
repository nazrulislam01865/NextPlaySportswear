@props(['steps' => [], 'currentStep' => 'information'])
@php
    $currentIndex = collect($steps)->search(fn ($step) => $step['key'] === $currentStep);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
@endphp
<nav class="np-checkout__stepper" aria-label="Checkout progress">
    @foreach($steps as $index => $step)
        @php($isDone = $index < $currentIndex)
        @php($isActive = $step['key'] === $currentStep)
        <div class="np-checkout__step {{ $isDone ? 'is-done' : ($isActive ? 'is-active' : '') }}" @if($isActive) aria-current="step" @endif>
            @if($isDone)
                <a class="np-checkout__step-circle" href="{{ route($step['route']) }}" aria-label="Edit {{ $step['label'] }}">&#10003;</a>
            @else
                <span class="np-checkout__step-circle">{{ $index + 1 }}</span>
            @endif
            <span class="np-checkout__step-label">{{ $step['label'] }}</span>
        </div>
        @unless($loop->last)<span class="np-checkout__step-line {{ $isDone ? 'is-done' : '' }}" aria-hidden="true"></span>@endunless
    @endforeach
</nav>
