<x-emails.notification-layout :email-message="$emailMessage" icon="notification-bulk-quote" :heading="'We received your bulk quote request'">
    <p class="np-mail-paragraph" style="margin:0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">We have received your quotation request and our team will review the details.</p>
    @php
        $details = [
            'Quotation Reference' => $emailMessage->details['Quotation Reference'] ?? $emailMessage->details['Reference'] ?? null,
            'Product' => $emailMessage->details['Product'] ?? $emailMessage->details['Product Type'] ?? null,
            'Quantity' => $emailMessage->details['Quantity'] ?? $emailMessage->details['Estimated Quantity'] ?? null,
            'Status' => ($emailMessage->details['Status'] ?? 'New') === 'New' ? 'Pending review' : ($emailMessage->details['Status'] ?? 'Pending review'),
        ];
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:{{ empty($emailMessage->attachments) ? '27px 0 27px' : '27px 0 0' }};border-collapse:collapse;"><tr><td>
        <x-emails.details-table :details="array_filter($details, static fn ($v) => filled($v))" label-width="43%" />
    </td></tr></table>
    <x-emails.attachment-files :attachments="$emailMessage->attachments" :fallback-url="$emailMessage->actionUrl" />
    <x-emails.help-card />
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" label="Track Your Quote" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>

