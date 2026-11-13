<x-emails.notification-layout :email-message="$emailMessage" icon="notification-shipment-truck" :heading="'Your shipment status changed'">
    <p class="np-mail-paragraph" style="margin:0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">We have a new shipping update for your NextPlay Sportswear order.</p>
    @php
        $details = [
            'Order Number' => $emailMessage->details['Order Number'] ?? null,
            'Shipment' => $emailMessage->details['Shipment'] ?? null,
            'Status' => $emailMessage->details['Status'] ?? null,
            'Carrier' => $emailMessage->details['Carrier'] ?? null,
            'Tracking Number' => $emailMessage->details['Tracking Number'] ?? null,
            'Estimated Delivery' => $emailMessage->details['Estimated Delivery'] ?? null,
        ];
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 27px;border-collapse:collapse;"><tr><td>
        <x-emails.details-table :details="array_filter($details, static fn ($v) => filled($v))" label-width="43%" />
    </td></tr></table>

    <x-emails.help-card />
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" label="Track Shipment" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>

