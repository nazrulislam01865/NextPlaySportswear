@props(['emailMessage', 'icon', 'heading', 'intro', 'details' => [], 'action' => '', 'preview' => false, 'approved' => false, 'mailMessage' => null])
<x-emails.notification-layout :email-message="$emailMessage" :icon="$icon" :heading="$heading" :internal="true">
    <p class="np-mail-paragraph" style="margin:0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">{{ $intro }}</p>
    @if ($details !== [])
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 0;border-collapse:collapse;"><tr><td>
            <x-emails.details-table :details="array_filter($details, static fn ($v) => filled($v))" label-width="43%" />
        </td></tr></table>
    @endif
    @if ($preview && $emailMessage->attachments !== [])
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:27px 0 0;">
            <tr><td>
                <p style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:19px;font-weight:700;line-height:1.45;">{{ $approved ? 'Approved artwork file' : 'Artwork file included' }}</p>
                <p style="margin:10px 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:14px;line-height:1.55;">{{ $approved ? 'The version shown below is the approved artwork proof.' : 'Review the artwork file and its version before taking action.' }}</p>
                <x-emails.artwork-file-preview :email-message="$emailMessage" :approved="$approved" :mail-message="$mailMessage" />
            </td></tr>
        </table>
    @endif
    <x-emails.attachment-files :attachments="$emailMessage->attachments" :fallback-url="$emailMessage->actionUrl" />
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:{{ $details === [] && $emailMessage->attachments === [] ? '27px' : '0' }} 0 0;"><tr><td>
        <x-emails.help-card />
    </td></tr></table>
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" :label="$action" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>
