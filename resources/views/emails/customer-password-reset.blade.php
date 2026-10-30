<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $emailMessage->subject }}</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;color:#0a2345;font-family:Arial,Helvetica,sans-serif;">
    @php
        $customerName = trim((string) ($emailMessage->recipients[0]['name'] ?? ''));
        $firstName = preg_split('/\s+/', $customerName)[0] ?? '';
        $expires = max(1, (int) ($emailMessage->details['Link Expires'] ?? config('auth.passwords.users.expire', 15)));
    @endphp
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f1f5f9;"><tr><td align="center" style="padding:32px 15px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:468px;background:#fff;border-radius:11px;">
            <tr><td style="padding:25px 30px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                    <td style="color:#d65b36;font-size:27px;font-weight:900;letter-spacing:-7px;padding-right:15px;">❯❯</td>
                    <td style="color:#0a2345;font-size:12px;font-weight:700;letter-spacing:2px;">NEXTPLAY SPORTSWEAR</td>
                </tr></table>
            </td></tr>
            <tr><td style="padding:0 30px;"><div style="height:1px;background:#dce3ee;"></div></td></tr>
            <tr><td style="padding:27px 36px 10px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;"><tr><td align="center">
                    <div style="width:17px;height:17px;border:3px solid #0a2345;border-bottom:0;border-radius:11px 11px 0 0;margin:0 auto -2px;"></div>
                    <div style="width:31px;height:29px;border:3px solid #0a2345;border-radius:4px;text-align:center;line-height:25px;font-size:13px;color:#0a2345;">●</div>
                </td></tr></table>
                <h1 style="font-size:23px;line-height:1.3;margin:18px 0 24px;color:#0a2345;">Reset your password</h1>
                <p style="font-size:14px;line-height:1.65;margin:0 0 13px;color:#0a2345;">Hi {{ $firstName !== '' ? $firstName : 'there' }},</p>
                <p style="font-size:14px;line-height:1.65;margin:0 0 14px;color:#0a2345;">We received a request to reset the password for your NextPlay Sportswear account.</p>
                <p style="font-size:14px;line-height:1.65;margin:0 0 20px;color:#0a2345;">Click below to choose a new password.</p>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="#d65b36" style="border-radius:7px;"><a href="{{ $emailMessage->actionUrl }}" style="display:inline-block;padding:14px 34px;background:#d65b36;border-radius:7px;color:#fff;font-size:14px;font-weight:700;text-decoration:none;">Reset password</a></td></tr></table>
                <p style="color:#61718a;font-size:12px;line-height:1.6;margin:20px 0 22px;">This link expires in <strong>{{ $expires }} minutes</strong> and can only be used once.</p>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f1f4f8;border-radius:7px;"><tr><td style="padding:15px 20px;">
                    <strong style="font-size:14px;color:#0a2345;">Didn’t request this?</strong>
                    <div style="font-size:12px;line-height:1.55;color:#60718a;margin-top:7px;">You can ignore this email. Your password will stay the same.</div>
                </td></tr></table>
                <p style="font-size:12px;line-height:1.5;color:#61718a;margin:18px 0;">Keep this link private. Don't share or forward this email.</p>
                <div style="height:1px;background:#dce3ee;"></div>
                <p style="font-size:12px;color:#61718a;line-height:1.6;margin:14px 0 0;">Need help? <a href="{{ route('contact') }}" style="color:#0a2345;text-decoration:underline;">Contact support.</a></p>
                <p style="font-size:12px;color:#61718a;margin:4px 0 0;">NextPlay Sportswear</p>
            </td></tr>
        </table>
    </td></tr></table>
</body>
</html>
