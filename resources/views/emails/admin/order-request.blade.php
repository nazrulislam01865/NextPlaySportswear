@php
    $rows = [
        'Request Number' => $emailMessage->details['Request Number'] ?? null,
        'Order Number' => $emailMessage->details['Order Number'] ?? null,
        'Request Type' => $emailMessage->details['Request Type'] ?? null,
        'Customer' => $emailMessage->details['Customer'] ?? null,
        'Customer Email' => $emailMessage->details['Customer Email'] ?? null,
        'Status' => $emailMessage->details['Status'] ?? null,
    ];
@endphp
<x-emails.admin-notification
    :email-message="$emailMessage"
    icon="notification-bulk-quote"
    heading="New customer order request"
    intro="A customer submitted an order request that needs review."
    :details="$rows"
    action="Open Admin Order"
/>
