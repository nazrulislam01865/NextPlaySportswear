@props(['status'])
@php
    $tone = match($status) {
        'approved' => 'approved',
        'needs_revision' => 'revision',
        'rejected' => 'rejected',
        default => 'pending'
    };
    $label = match($status) {
        'approved' => 'Approved',
        'needs_revision' => 'Needs Revision',
        'rejected' => 'Rejected',
        default => 'Pending Review'
    };
    $icon = match($status) {
        'approved' => 'check-circle',
        'needs_revision' => 'refresh',
        'rejected' => 'alert',
        default => 'clock'
    };
@endphp
<span class="np-artwork-status np-artwork-status--{{ $tone }}"><x-storefront.account.icon :name="$icon" :size="19" />{{ $label }}</span>
