<x-emails.notification-layout :email-message="$emailMessage" icon="notification-guest-reply" :heading="'Quotation: Your quote is ready'">
    <p class="np-mail-paragraph" style="margin:0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">Your team has reviewed the quotation request and added an update for you.</p>
    {{-- Preserve a real individual staff update when this event carries one. --}}
    @if (filled($emailMessage->introLines[0] ?? null) && trim((string) $emailMessage->introLines[0]) !== 'Your custom quotation is available for review.')
        <p class="np-mail-paragraph" style="margin:13px 0 0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:18px;line-height:1.5;">{{ $emailMessage->introLines[0] }}</p>
    @endif
    @php
        $details = [
            'Quotation Reference' => $emailMessage->details['Quotation Reference'] ?? $emailMessage->details['Quote request'] ?? null,
            'Product' => $emailMessage->details['Product'] ?? null,
            'Status' => ($emailMessage->details['Status'] ?? null) === 'Quotation Sent' ? 'Ready for review' : ($emailMessage->details['Status'] ?? null),
        ];
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:{{ empty($emailMessage->attachments) ? '27px 0 27px' : '27px 0 0' }};border-collapse:collapse;"><tr><td>
        <x-emails.details-table :details="array_filter($details, static fn ($v) => filled($v))" label-width="43%" />
    </td></tr></table>
    <x-emails.attachment-files :attachments="$emailMessage->attachments" :fallback-url="$emailMessage->actionUrl" />
    <x-emails.help-card />
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" label="Track Quote" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>

