<x-emails.notification-layout :email-message="$emailMessage" icon="notification-refresh" :heading="'Your order request changed'">
    <p class="np-mail-paragraph" style="margin:0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">There is a new update on your order change or cancellation request.</p>
    @php
        $details = [
            'Request Number' => $emailMessage->details['Request Number'] ?? null,
            'Order Number' => $emailMessage->details['Order Number'] ?? null,
            'Status' => $emailMessage->details['Status'] ?? null,
        ];
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 {{ empty($emailMessage->attachments) ? '27px' : '0' }};border-collapse:collapse;"><tr><td>
        <x-emails.details-table :details="array_filter($details, static fn ($v) => filled($v))" label-width="43%" />
    </td></tr></table>
    <x-emails.attachment-files :attachments="$emailMessage->attachments" :fallback-url="$emailMessage->actionUrl" />
    <x-emails.help-card />
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" label="View Order" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>
