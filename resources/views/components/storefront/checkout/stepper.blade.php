@props(['steps' => [], 'currentStep' => 'information'])
@php
    $currentIndex = collect($steps)->search(fn ($step) => $step['key'] === $currentStep);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $visibleSteps = $currentStep === 'review'
        ? array_merge($steps, [['key' => 'placed', 'label' => 'Order placed', 'route' => 'order.confirmation']])
        : $steps;
@endphp
<nav class="np-checkout__stepper" aria-label="Checkout progress">
    @foreach($visibleSteps as $index => $step)
        @php
            $done = $index < $currentIndex;
            $active = $step['key'] === $currentStep;
        @endphp
        <div class="np-checkout__step {{ $done ? 'is-done' : ($active ? 'is-active' : '') }}" @if($active) aria-current="step" @endif>
            <div class="np-checkout__step-head">
                @if($done)
                    <a class="np-checkout__step-circle" href="{{ route($step['route']) }}" aria-label="Edit {{ $step['label'] }}">✓</a>
                @else
                    <span class="np-checkout__step-circle">{{ $index + 1 }}</span>
                @endif
            </div>
            <span class="np-checkout__step-label">{{ $step['label'] }}</span>
            @if($done)<small>Complete</small>@endif
        </div>
        @unless($loop->last)<span class="np-checkout__step-line {{ $done ? 'is-done' : '' }}" aria-hidden="true"></span>@endunless
    @endforeach
</nav>
