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

                            @php
                                $isDeliveryUpdate = in_array($emailMessage->key, ['shipment.delivery-estimate-updated', 'template.delivery-estimate-updated'], true);
                                $prevEstimate = $emailMessage->details['Previous estimate'] ?? $emailMessage->details['Previous Estimate'] ?? null;
                                $updEstimate = $emailMessage->details['Updated estimate'] ?? $emailMessage->details['Estimated Delivery'] ?? null;
                                $holidayAffecting = $emailMessage->details['Holiday affecting delivery'] ?? $emailMessage->details['Reason'] ?? null;
                                $orderRefNum = $emailMessage->details['Order number'] ?? $emailMessage->details['Order Number'] ?? null;
                            @endphp

                            @if ($isDeliveryUpdate && ($prevEstimate || $updEstimate))
                                <div style="margin:22px 0;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td width="50%" valign="top" style="padding:10px 14px;">
                                                <div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;">Previous estimate</div>
                                                <div style="font-size:17px;font-weight:800;color:#0b1a30;">{{ $prevEstimate ?? '—' }}</div>
                                            </td>
                                            <td width="50%" valign="top" style="padding:10px 14px;">
                                                <div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;">Updated estimate</div>
                                                <div style="font-size:17px;font-weight:800;color:#0b1a30;">{{ $updEstimate ?? '—' }}</div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                @if (!empty($holidayAffecting))
                                    <div style="margin:16px 0 22px;background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:16px 18px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="26" valign="top" style="padding-right:12px;">
                                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#CF5D38" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                                    </svg>
                                                </td>
                                                <td>
                                                    <div style="font-size:13px;font-weight:800;color:#0b1a30;margin-bottom:2px;">Holiday affecting delivery</div>
                                                    <div style="font-size:12px;font-weight:600;color:#92400e;">{{ $holidayAffecting }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                @endif
                            @elseif ($emailMessage->details !== [])
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
                                        <td bgcolor="#CF5D38" style="border-radius:10px;">
                                            <a href="{{ $emailMessage->actionUrl }}" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;background:#CF5D38;border-radius:10px;">
                                                {{ $emailMessage->actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($isDeliveryUpdate && !empty($orderRefNum))
                                <p style="margin:16px 0 0;font-size:12px;font-weight:600;color:#64748b;">
                                    Order {{ str_starts_with($orderRefNum, '#') ? $orderRefNum : '#' . $orderRefNum }}
                                </p>
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
