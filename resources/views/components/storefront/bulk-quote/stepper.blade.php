@php
    $steps = [
        ['number' => '1', 'label' => 'Contact'],
        ['number' => '2', 'label' => 'Order Details'],
        ['number' => '3', 'label' => 'Shipping'],
        ['number' => '4', 'label' => 'Attachments'],
    ];
@endphp

<ol class="bulk-quote-stepper" aria-label="Bulk quote form steps">
    @foreach($steps as $index => $step)
        <li class="bulk-quote-stepper-item {{ $index === 0 ? 'is-active' : '' }}" @if($index === 0) aria-current="step" @endif>
            <span class="bulk-quote-stepper-dot">{{ $step['number'] }}</span>
            <span class="bulk-quote-stepper-label">{{ $step['label'] }}</span>
        </li>
    @endforeach
</ol>
