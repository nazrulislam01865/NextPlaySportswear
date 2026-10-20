@props(['quote'])
@php
    $status = $quote->status;
    [$tone, $label] = match (true) {
        in_array($status, ['accepted', 'converted'], true) => ['confirmed', $quote->converted_order_id ? 'Order Created' : 'Confirmed'],
        in_array($status, ['closed', 'rejected'], true) => ['closed', 'Closed'],
        $quote->guest_decision === 'changes_requested' => ['discussion', 'Changes Requested'],
        $status === 'customer_responded' => ['discussion', 'In Discussion'],
        $status === 'quotation_sent' => ['sent', 'Quotation Sent'],
        default => ['pending', 'Pending Review'],
    };
@endphp
<span class="np-rq-status np-rq-status--{{ $tone }}"><span class="np-rq-status-dot"></span>{{ $label }}</span>
