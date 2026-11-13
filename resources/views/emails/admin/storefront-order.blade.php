@php
    $rows = [
        'Order Number' => $emailMessage->details['Order Number'] ?? null,
        'Customer' => $emailMessage->details['Customer'] ?? null,
        'Email' => $emailMessage->details['Customer Email'] ?? null,
        'Items' => $emailMessage->details['Items'] ?? null,
        'Order Total' => $emailMessage->details['Order Total'] ?? null,
    ];
@endphp
<x-emails.admin-notification :email-message="$emailMessage" icon="notification-order-received" heading="New storefront order" :intro="'A new customer order has been placed on NextPlay Sportswear.'" :details="$rows" action="Open Admin Order" :preview="false" :approved="false" />
