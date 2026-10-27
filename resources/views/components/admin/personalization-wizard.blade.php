@props(['step' => 1, 'template' => null])
@php
    $steps = [1 => 'Basic Information', 2 => 'Configure Fields', 3 => 'Preview & Save'];
@endphp
<div class="pt-wizard" aria-label="Template setup progress">
    @foreach($steps as $number => $label)
        <div class="pt-wizard-step {{ $number === $step ? 'is-current' : ($number < $step ? 'is-done' : '') }}" @if($number === $step) aria-current="step" @endif>
            <span>{{ $number < $step ? '✓' : $number }}</span> {{ $label }}
        </div>
    @endforeach
</div>
