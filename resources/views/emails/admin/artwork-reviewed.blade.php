@php
    $rows = [
        'Order Number' => $emailMessage->details['Order Number'] ?? null,
        'Proof Version' => $emailMessage->details['Proof Version'] ?? null,
        'Status' => $emailMessage->details['Decision'] ?? null,
        'Customer Feedback' => $emailMessage->details['Customer Feedback'] ?? null,
    ];
@endphp
<x-emails.admin-notification :email-message="$emailMessage" icon="notification-artwork" heading="Customer reviewed an artwork proof" :intro="'A customer has reviewed the published artwork proof.'" :details="$rows" action="View Admin Order" :preview="true" :mail-message="$message ?? null" :approved="strcasecmp((string) ($emailMessage->details['Decision'] ?? ''), 'Approved') === 0" />
