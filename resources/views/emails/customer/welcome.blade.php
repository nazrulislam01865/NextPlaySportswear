<x-emails.notification-layout :email-message="$emailMessage" icon="notification-welcome">
    @foreach ($emailMessage->introLines as $line)
        <p class="np-mail-paragraph" style="margin:0 0 {{ $loop->last ? '0' : '26px' }};color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">{{ $line }}</p>
    @endforeach

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 27px;border-collapse:collapse;"><tr><td>
        <x-emails.help-card />
    </td></tr></table>

    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" :label="$emailMessage->actionText ?: 'Open My Account'" />
        </td></tr></table>
    @endif

    @foreach ($emailMessage->outroLines as $line)
        <p class="np-mail-paragraph" style="margin:{{ $loop->first ? '24px' : '13px' }} 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:16px;line-height:1.5;">{{ $line }}</p>
    @endforeach
</x-emails.notification-layout>
