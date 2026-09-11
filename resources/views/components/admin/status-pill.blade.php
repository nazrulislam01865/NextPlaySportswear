@props(['status'])
@php
    $value = (string) $status;
    $tone = match($value) {
        'paid', 'delivered', 'completed', 'approved', 'issued', 'fulfilled', 'synced', 'accepted', 'converted', 'qualified' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'failed', 'cancelled', 'rejected', 'exception' => 'bg-red-50 text-red-700 border-red-200',
        'pending_payment', 'pending', 'requested', 'under_review', 'processing', 'payment_review', 'quote_invoice_requested', 'quotation_preparing' => 'bg-amber-50 text-amber-800 border-amber-200',
        'in_production', 'partially_shipped', 'shipped', 'in_transit', 'out_for_delivery', 'label_issued', 'partial', 'syncing', 'quotation_sent', 'customer_responded' => 'bg-blue-50 text-blue-700 border-blue-200',
        'urgent' => 'bg-red-50 text-red-700 border-red-200',
        'high' => 'bg-orange-50 text-orange-700 border-orange-200',
        'low', 'disabled', 'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
        default => 'bg-slate-50 text-slate-700 border-slate-200',
    };
@endphp
<span {{ $attributes->merge(['class' => 'admin-status-pill border px-3 py-1 text-[11px] font-black uppercase tracking-wide '.$tone]) }}>{{ str($value)->headline() }}</span>
