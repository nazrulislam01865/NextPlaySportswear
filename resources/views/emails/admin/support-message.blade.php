@php
    $rows = [
        'Name' => $emailMessage->details['Name'] ?? null,
        'Email' => $emailMessage->details['Email'] ?? null,
        'Phone' => $emailMessage->details['Phone'] ?? null,
        'Topic' => $emailMessage->details['Topic'] ?? null,
        'Order Number' => $emailMessage->details['Order Number'] ?? null,
        'Message' => $emailMessage->details['Message'] ?? null,
    ];
@endphp
<x-emails.admin-notification :email-message="$emailMessage" icon="notification-envelope" heading="New customer support message" :intro="'A new contact form submission has been stored in the NextPlay backend.'" :details="$rows" action="Open Admin Contact" :preview="false" :approved="false" />
