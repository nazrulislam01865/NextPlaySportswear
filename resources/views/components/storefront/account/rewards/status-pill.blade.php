@props(['status'])

@php
    $normalized = strtolower(trim((string) $status));
    $label = match ($normalized) {
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        default => 'Pending',
    };
@endphp

<span {{ $attributes->class(['np-referral-status', 'is-completed' => $normalized === 'completed', 'is-pending' => $normalized !== 'completed']) }}>{{ $label }}</span>
