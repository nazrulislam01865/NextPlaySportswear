@props(['current' => 1])

<div class="np-referral-order-stepper" aria-label="Checkout progress">
    @foreach ([1 => 'Basket', 2 => 'Checkout', 3 => 'Confirmation'] as $number => $label)
        @php($state = $number < $current ? 'done' : ($number === $current ? 'current' : 'pending'))
        <div class="np-referral-order-step is-{{ $state }}">
            <span class="np-referral-order-step__dot">
                @if ($state === 'done')
                    <x-storefront.referral.icon name="check" :size="16" />
                @else
                    {{ $number }}
                @endif
            </span>
            <strong>{{ $label }}</strong>
        </div>
        @if ($number < 3)
            <span class="np-referral-order-step__line" aria-hidden="true"></span>
        @endif
    @endforeach
</div>
