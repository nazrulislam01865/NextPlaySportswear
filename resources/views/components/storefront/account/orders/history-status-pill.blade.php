@props([
    'status',
    'type' => 'order',
])

@php
    $label = $type === 'payment'
        ? (string) config('commerce.payment_statuses.'.$status, \Illuminate\Support\Str::headline((string) $status))
        : (string) config('commerce.order_statuses.'.$status, \Illuminate\Support\Str::headline((string) $status));

    $tone = match($status) {
        'paid', 'completed', 'delivered' => 'green',
        'design_review', 'proof_approval', 'in_production', 'partially_shipped', 'shipped', 'processing' => 'blue',
        'pending_payment', 'payment_review', 'quote_invoice_requested', 'pending' => 'orange',
        'failed', 'payment_failed', 'cancelled' => 'red',
        'refunded', 'partially_refunded' => 'purple',
        default => 'slate',
    };
@endphp

<span class="np-order-history-status is-{{ $tone }}">{{ $label }}</span>
