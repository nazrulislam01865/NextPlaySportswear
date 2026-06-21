@props(['status' => 'pending', 'tone' => null])

@php
    $normalized = \Illuminate\Support\Str::of($status)->replace(['_', '-'], ' ')->headline();
    $tone = $tone ?? match ($status) {
        'verified', 'paid', 'success', 'delivered' => 'success',
        'failed', 'cancelled', 'refunded' => 'danger',
        'pending_payment', 'payment_review', 'design_review', 'quote_invoice_requested' => 'warning',
        default => 'info',
    };

    $classes = match ($tone) {
        'success' => 'border-green-200 bg-green-50 text-green-700',
        'danger' => 'border-red-200 bg-red-50 text-red-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        default => 'border-blue-200 bg-blue-50 text-blue-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-1 text-xs font-black uppercase tracking-wide ' . $classes]) }}>
    {{ $slot->isEmpty() ? $normalized : $slot }}
</span>
