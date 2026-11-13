@php
    $rows = [
        'Request Number' => $emailMessage->details['Request Number'] ?? null,
        'Type' => $emailMessage->details['Type'] ?? null,
        'Order Number' => $emailMessage->details['Order Number'] ?? null,
        'Customer' => $emailMessage->details['Customer'] ?? null,
        'Customer Email' => $emailMessage->details['Customer Email'] ?? null,
        'Status' => $emailMessage->details['Status'] ?? null,
    ];
@endphp
<x-emails.admin-notification :email-message="$emailMessage" icon="notification-return" heading="New return or exchange request" :intro="'A customer submitted a new request that is ready for eligibility review.'" :details="$rows" action="Open Admin Request" :preview="false" :approved="false" />
