@props(['amount' => 0])

@php($sizeCharge = max(0, (float) $amount))

<span {{ $attributes->class($sizeCharge > 0 ? 'font-black text-brand-blue' : 'text-slate-400') }}>
    @if($sizeCharge > 0)
        +${{ number_format($sizeCharge, 2) }} / piece
    @else
        Included
    @endif
</span>
