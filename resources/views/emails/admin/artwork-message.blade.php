@php
    $rows = [
        'Order Number' => $emailMessage->details['Order Number'] ?? null,
        'Proof Version' => $emailMessage->details['Proof Version'] ?? null,
    ];
@endphp
<x-emails.admin-notification :email-message="$emailMessage" icon="notification-guest-reply" heading="Customer message about artwork" :intro="'There is a new message about artwork proof '.($emailMessage->details['Proof Version'] ?? '').'.'" :details="$rows" action="View Admin Order" :preview="true" :mail-message="$message ?? null" :approved="false" />
