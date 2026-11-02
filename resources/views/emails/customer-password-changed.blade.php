<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $emailMessage->subject }}</title>
    <style>
        /* Email-only responsive sizes. All critical styles are also inline. */
        @media screen and (max-width: 640px) {
            .np-mail-outer { padding:20px 12px !important; }
            .np-mail-card { width:100% !important; }
            .np-mail-inner { padding:30px 25px 25px !important; }
            .np-mail-heading { font-size:32px !important; }
            .np-mail-paragraph { font-size:16px !important; }
            .np-mail-alert { padding:19px 20px !important; }
        }
    </style>
</head>
@php
    $customerName = trim((string) ($emailMessage->recipients[0]['name'] ?? ''));
    $firstName = preg_split('/\s+/u', $customerName, 2)[0] ?? '';
    $firstName = $firstName !== '' ? $firstName : 'there';
    $supportUrl = $emailMessage->actionUrl ?: route('contact');
    $theme = config('transactional_email.design');
    $logoUrl = \App\Support\TransactionalMailBrand::headerLogoUrl();
    $lockUrl = \App\Support\TransactionalMailBrand::lockIconUrl();
@endphp
<body style="margin:0;padding:0;width:100%;background-color:{{ $theme['background'] }};color:{{ $theme['text'] }};font-family:{{ $theme['font'] }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;background-color:{{ $theme['background'] }};">
        <tr>
            <td class="np-mail-outer" align="center" style="padding:37px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="np-mail-card" style="width:600px;max-width:100%;margin:0 auto;background-color:#ffffff;border-collapse:separate;border-spacing:0;border-radius:14px;">
                    <tr>
                        <td class="np-mail-inner" style="padding:34px 43px 34px;">
                            <x-emails.header-logo :logo-url="$logoUrl" />

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:31px 0 0;">
                                        <img src="{{ $lockUrl }}" alt="" aria-hidden="true" width="60" height="77" style="width:60px;height:77px;max-width:100%;display:block;border:0;outline:none;text-decoration:none;" />
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:24px 0 0;">
                                        <h1 class="np-mail-heading" style="margin:0;color:{{ $theme['navy'] }};font-family:{{ $theme['font'] }};font-size:40px;font-weight:700;line-height:1.15;letter-spacing:-0.7px;">Your password has<br>been changed</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:42px 0 0;">
                                        <p class="np-mail-paragraph" style="margin:0;color:{{ $theme['text'] }};font-family:{{ $theme['font'] }};font-size:20px;line-height:1.5;">Hi {{ $firstName }},</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:34px 0 0;">
                                        <p class="np-mail-paragraph" style="margin:0;color:{{ $theme['text'] }};font-family:{{ $theme['font'] }};font-size:20px;line-height:1.55;">The password for your NextPlay Sportswear account has been changed successfully.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:43px 0 0;">
                                        <p class="np-mail-paragraph" style="margin:0;color:{{ $theme['text'] }};font-family:{{ $theme['font'] }};font-size:20px;line-height:1.55;">If you made this change, no further action is needed.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:42px 0 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:separate;background:{{ $theme['soft'] }};border-radius:9px;">
                                            <tr>
                                                <td class="np-mail-alert" style="padding:29px 25px 32px;">
                                                    <p style="margin:0;color:{{ $theme['navy'] }};font-family:{{ $theme['font'] }};font-size:20px;font-weight:700;line-height:1.4;">Didn’t make this change?</p>
                                                    <p style="margin:8px 0 0;color:{{ $theme['muted'] }};font-family:{{ $theme['font'] }};font-size:18px;line-height:1.5;">Contact our support team immediately so we can help secure your account.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:20px 0 0;">
                                        <x-emails.action-button :url="$supportUrl" label="Contact support" />
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:27px 0 28px;">
                                        <p style="margin:0;color:{{ $theme['muted'] }};font-family:{{ $theme['font'] }};font-size:16px;line-height:1.6;">This is an automated account security notification.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td><x-emails.support-footer :support-url="$supportUrl" /></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
