@props(['emailMessage', 'icon', 'heading' => null, 'internal' => false, 'iconWidth' => 65, 'iconHeight' => 65])
@php
    $theme = config('transactional_email.design');
    $customerName = trim((string) ($emailMessage->recipients[0]['name'] ?? ''));
    $firstName = preg_split('/\s+/u', $customerName, 2)[0] ?? '';
    $firstName = $firstName !== '' ? $firstName : 'there';
    $logoUrl = \App\Support\TransactionalMailBrand::headerLogoUrl();
    $iconUrl = rtrim((string) config('app.url'), '/').'/images/email/'.$icon.'.png';
    $supportUrl = route('contact');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $emailMessage->subject }}</title>
    <style>
        /* Only responsive overrides belong here; critical email styles are inline. */
        @media screen and (max-width: 640px) {
            .np-mail-outer { padding:20px 12px !important; }
            .np-mail-card { width:100% !important; }
            .np-mail-inner { padding:30px 25px 25px !important; }
            .np-mail-heading { font-size:32px !important; }
            .np-mail-paragraph { font-size:16px !important; }
            .np-mail-detail-cell { padding:12px !important; font-size:14px !important; }
            .np-mail-detail-label { width:38% !important; }
            .np-mail-estimate-value { font-size:17px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:{{ $theme['background'] }};color:{{ $theme['text'] }};font-family:{{ $theme['font'] }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;background-color:{{ $theme['background'] }};">
        <tr>
            <td class="np-mail-outer" align="center" style="padding:37px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="np-mail-card" style="width:600px;max-width:100%;margin:0 auto;background-color:#ffffff;border-collapse:separate;border-spacing:0;border-radius:14px;">
                    <tr>
                        <td class="np-mail-inner" style="padding:34px 43px 34px;">
                            <x-emails.header-logo :logo-url="$logoUrl" />
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:26px 0 0;">
                                        <img src="{{ $iconUrl }}" alt="" aria-hidden="true" width="{{ $iconWidth }}" height="{{ $iconHeight }}" style="display:block;width:{{ $iconWidth }}px;height:{{ $iconHeight }}px;border:0;outline:none;text-decoration:none;" />
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 0 0;">
                                        <h1 class="np-mail-heading" style="margin:0;color:{{ $theme['navy'] }};font-family:{{ $theme['font'] }};font-size:39px;font-weight:700;line-height:1.13;letter-spacing:-0.8px;">{{ $heading ?? $emailMessage->heading }}</h1>
                                    </td>
                                </tr>
                                @unless ($internal)
                                <tr>
                                    <td style="padding:29px 0 0;">
                                        <p class="np-mail-paragraph" style="margin:0;color:{{ $theme['text'] }};font-family:{{ $theme['font'] }};font-size:20px;line-height:1.5;">Hi {{ $firstName }},</p>
                                    </td>
                                </tr>
                                @endunless
                                <tr>
                                    <td style="padding:16px 0 0;">{{ $slot }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:27px 0 28px;">
                                        <p style="margin:0;color:{{ $theme['muted'] }};font-family:{{ $theme['font'] }};font-size:16px;line-height:1.6;">{{ $internal ? 'This is an internal NextPlay Sportswear notification.' : 'This is a transactional message from NextPlay Sportswear.' }}</p>
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
