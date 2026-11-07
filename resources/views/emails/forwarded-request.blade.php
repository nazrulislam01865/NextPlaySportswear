<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailMessage->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;color:#14213d;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#f4f7fb;">
        <tr>
            <td align="center" style="padding:26px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:680px;border:1px solid #e2e8f0;border-radius:14px;background:#ffffff;">
                    <tr>
                        <td style="background:#0f2b4d;padding:25px 27px;color:#ffffff;">
                            <div style="font-size:12px;line-height:1.5;letter-spacing:1.4px;font-weight:700;text-transform:uppercase;color:#d5e3f2;">
                                {{ config('transactional_email.brand.name', 'NextPlay Sportswear') }}
                            </div>
                            <div style="margin:10px 0 0;font-size:23px;line-height:1.35;font-weight:800;color:#ffffff;">
                                {{ $emailMessage->heading }}
                            </div>
                            <div style="margin-top:10px;font-size:12px;color:#d5e3f2;">
                                {{ $emailMessage->key === 'admin.confirmation-auto-forward' ? 'Automatically forwarded request' : 'Administrator-forwarded request' }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:25px 25px 10px;">
                            @foreach ($emailMessage->introLines as $line)
                                @if (filled($line))
                                    <p style="margin:0 0 13px;font-size:14px;line-height:1.7;color:#475569;">
                                        {!! nl2br(e($line)) !!}
                                    </p>
                                @endif
                            @endforeach

                            @foreach ($emailMessage->sections as $section)
                                @if (!empty($section['rows']))
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:8px 0 22px;border-collapse:collapse;border:1px solid #e2e8f0;">
                                        <tr>
                                            <td colspan="2" style="padding:13px 15px;border-bottom:2px solid #d6e2ef;background:#eff4fa;color:#0f2b4d;font-size:14px;font-weight:800;line-height:1.5;">
                                                {{ $section['title'] }}
                                            </td>
                                        </tr>
                                        @foreach ($section['rows'] as $row)
                                            @if ($row['group'])
                                                <tr>
                                                    <td colspan="2" style="padding:10px 14px 8px {{ 14 + min(8, (int) $row['depth']) * 10 }}px;border-bottom:1px solid #e2e8f0;background:#f8fafc;color:#26496a;font-size:12px;font-weight:800;line-height:1.5;word-break:break-word;">
                                                        {{ $row['label'] }}
                                                    </td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <td valign="top" width="37%" style="width:37%;padding:10px 10px 10px {{ 14 + min(8, (int) $row['depth']) * 10 }}px;border-bottom:1px solid #edf2f7;color:#64748b;font-size:12px;font-weight:700;line-height:1.55;word-break:break-word;">
                                                        {{ $row['label'] }}
                                                    </td>
                                                    <td valign="top" style="padding:10px 14px 10px 8px;border-bottom:1px solid #edf2f7;color:#0f2b4d;font-size:13px;line-height:1.6;word-break:break-word;">
                                                        {!! nl2br(e($row['value'])) !!}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </table>
                                @endif
                            @endforeach

                            @if (filled($emailMessage->actionText) && filled($emailMessage->actionUrl))
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 20px;">
                                    <tr>
                                        <td bgcolor="#cf5d38" style="border-radius:7px;">
                                            <a href="{{ $emailMessage->actionUrl }}" style="display:inline-block;padding:13px 20px;background:#cf5d38;border-radius:7px;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">
                                                {{ $emailMessage->actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @foreach ($emailMessage->outroLines as $line)
                                <p style="margin:0 0 14px;color:#64748b;font-size:13px;line-height:1.65;">
                                    {{ $line }}
                                </p>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:17px 25px;background:#f8fafc;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;line-height:1.6;">
                            Internal forwarding notification from {{ config('transactional_email.brand.name', 'NextPlay Sportswear') }}.
                            Full attachments, when available, are included with this email. The admin link requires a NextPlay administrator login.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
