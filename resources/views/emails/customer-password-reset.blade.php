@php
    // Keep the existing reset URL, expiry, and any published email-customization text.
    // Presentation is shared with the other screenshot-matched emails.
    $expiry = (int) ($emailMessage->details['Link Expires'] ?? config('auth.passwords.users.expire', 15));
    $expiry = max(1, $expiry);
    $details = [
        'Account Email' => $emailMessage->details['Account Email'] ?? ($emailMessage->recipients[0]['email'] ?? null),
        'Link Expires' => $emailMessage->details['Link Expires'] ?? ($expiry.' minutes'),
        ...array_diff_key($emailMessage->details, array_flip(['Account Email', 'Link Expires'])),
    ];
    $introLines = $emailMessage->introLines !== [] ? $emailMessage->introLines : [
        'We received a request to reset the password for your NextPlay Sportswear customer account.',
        'Use the secure button below to choose a new password. If you did not request this, you can ignore this email.',
    ];
    $outroLines = $emailMessage->outroLines !== [] ? $emailMessage->outroLines : [
        'This reset link can only be used once.',
        'For your security, never forward this reset link to anyone.',
    ];
@endphp
<x-emails.notification-layout :email-message="$emailMessage" icon="password-changed-lock" :icon-width="54" :icon-height="70" heading="Reset your password">
    @foreach ($introLines as $line)
        <p class="np-mail-paragraph" style="margin:0 0 {{ $loop->last ? '0' : '25px' }};color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">{{ $line }}</p>
    @endforeach

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0;border-collapse:collapse;"><tr><td>
        <x-emails.details-table :details="array_filter($details, static fn ($v) => filled($v))" label-width="43%" />
    </td></tr></table>

    <x-emails.help-card title="Didn’t request a password reset?" description="You can ignore this email. Your password will not change unless you use the reset link." />
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" label="Reset Password" />
        </td></tr></table>
    @endif
    @foreach ($outroLines as $line)
        @if (filled($line))
            <p class="np-mail-paragraph" style="margin:24px 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:16px;line-height:1.5;">{{ $line }}</p>
        @endif
    @endforeach
</x-emails.notification-layout>
