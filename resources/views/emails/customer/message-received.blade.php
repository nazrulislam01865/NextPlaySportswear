<x-emails.notification-layout :email-message="$emailMessage" icon="notification-envelope">
    @foreach ($emailMessage->introLines as $line)
        <p class="np-mail-paragraph" style="margin:0 0 {{ $loop->last ? '0' : '13px' }};color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.5;">{{ $line }}</p>
    @endforeach

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:23px 0 22px;border-collapse:collapse;"><tr><td>
        <x-emails.details-table :details="$emailMessage->details" />
    </td></tr></table>

    <x-emails.help-card />
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:19px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" :label="$emailMessage->actionText ?: 'Visit Help Center'" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>
