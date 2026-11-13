@php
    $rows = [
        'Quotation Reference' => $emailMessage->details['Reference'] ?? null,
        'Customer' => $emailMessage->details['Customer'] ?? null,
        'Email' => $emailMessage->details['Email'] ?? null,
        'Product' => $emailMessage->details['Product Type'] ?? null,
        'Quantity' => $emailMessage->details['Estimated Quantity'] ?? null,
    ];
@endphp
<x-emails.admin-notification :email-message="$emailMessage" icon="notification-bulk-quote" heading="New bulk quote request" :intro="'A customer submitted a new bulk quote request.'" :details="$rows" action="Open Quote in Admin" :preview="false" :approved="false" />
