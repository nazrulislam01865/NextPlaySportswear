<x-emails.notification-layout :email-message="$emailMessage" icon="notification-shipment-truck">
    @foreach ($emailMessage->introLines as $line)
        <p class="np-mail-paragraph" style="margin:0 0 {{ $loop->last ? '0' : '25px' }};color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">{{ $line }}</p>
    @endforeach

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 27px;border-collapse:collapse;"><tr><td>
        <x-emails.details-table :details="$emailMessage->details" label-width="43%" />
    </td></tr></table>

    <x-emails.help-card />
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" :label="$emailMessage->actionText ?: 'View Shipment'" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>
