<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailMessage->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f4f7fb;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:24px 28px;background:#0f2b4d;color:#ffffff;">
                            <div style="font-size:12px;letter-spacing:1.6px;text-transform:uppercase;font-weight:700;color:#cbd5e1;">
                                {{ config('transactional_email.brand.name', 'NextPlay Sportswear') }}
                            </div>
                            <div style="margin-top:8px;font-size:28px;line-height:1.25;font-weight:800;">
                                {{ $emailMessage->heading }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            @foreach ($emailMessage->introLines as $line)
                                <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#475569;">
                                    {{ $line }}
                                </p>
                            @endforeach

                            @if ($emailMessage->details !== [])
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:22px 0;border-collapse:separate;border-spacing:0;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                                    @foreach ($emailMessage->details as $label => $value)
                                        <tr>
                                            <td valign="top" style="width:38%;padding:12px 14px;background:#f8fafc;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};font-size:13px;font-weight:700;color:#475569;">
                                                {{ $label }}
                                            </td>
                                            <td valign="top" style="padding:12px 14px;border-bottom:{{ $loop->last ? '0' : '1px solid #e2e8f0' }};font-size:13px;line-height:1.5;color:#0f172a;word-break:break-word;">
                                                {{ $value }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if (filled($emailMessage->actionText) && filled($emailMessage->actionUrl))
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
                                    <tr>
                                        <td bgcolor="#ef1f3f" style="border-radius:10px;">
                                            <a href="{{ $emailMessage->actionUrl }}" style="display:inline-block;padding:13px 22px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                                                {{ $emailMessage->actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @foreach ($emailMessage->outroLines as $line)
                                <p style="margin:14px 0 0;font-size:14px;line-height:1.65;color:#64748b;">
                                    {{ $line }}
                                </p>
                            @endforeach
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:12px;line-height:1.6;color:#64748b;">
                            This is a transactional message from {{ config('transactional_email.brand.name', 'NextPlay Sportswear') }}.
                            @if (filled(config('transactional_email.brand.support_email')))
                                Need help? Contact {{ config('transactional_email.brand.support_email') }}.
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
