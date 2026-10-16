<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'Delivery estimate updated for your order' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0b1a30; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f9; margin: 0; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);">
                    <tr>
                        <td style="padding: 36px 36px 32px;">
                            <!-- Header / Brand -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px;">
                                <tr>
                                    <td>
                                        <span style="font-size: 22px; font-weight: 900; letter-spacing: -0.5px; text-transform: uppercase;">
                                            <span style="color: #0b1a30;">NEXT</span><span style="color: #CF5D38;">PLAY</span>
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Heading -->
                            <h1 style="margin: 0 0 16px; font-size: 24px; font-weight: 800; line-height: 1.3; color: #0b1a30;">
                                {{ $heading ?? 'Delivery estimate updated for your order' }}
                            </h1>

                            <!-- Salutation & Intro -->
                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6; color: #334155; font-weight: 500;">
                                Hi {{ $customerName ?? 'John' }},
                            </p>
                            <p style="margin: 0 0 26px; font-size: 15px; line-height: 1.6; color: #64748b;">
                                {{ $introText ?? ('Your estimated delivery date has been updated because ' . ($holidayReason ?? 'Christmas Day') . ' is a non-delivery date for your destination.') }}
                            </p>

                            <!-- Estimate Comparison Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 20px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
                                <tr>
                                    <td width="50%" valign="top" style="padding: 18px 20px; border-right: 1px solid #e2e8f0;">
                                        <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: none; margin-bottom: 6px;">
                                            Previous estimate
                                        </div>
                                        <div style="font-size: 19px; font-weight: 800; color: #0b1a30; line-height: 1.2;">
                                            {{ $previousEstimate ?? '18 Dec - 25 Dec 2026' }}
                                        </div>
                                    </td>
                                    <td width="50%" valign="top" style="padding: 18px 20px;">
                                        <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: none; margin-bottom: 6px;">
                                            Updated estimate
                                        </div>
                                        <div style="font-size: 19px; font-weight: 800; color: #0b1a30; line-height: 1.2;">
                                            {{ $updatedEstimate ?? '18 Dec - 28 Dec 2026' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Amber Holiday Callout Box -->
                            @if (!empty($holidayReason) || !empty($holidayDate))
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 26px; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;">
                                <tr>
                                    <td width="28" valign="top" style="padding: 16px 0 16px 18px;">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#CF5D38" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; margin-top: 1px;">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                    </td>
                                    <td valign="top" style="padding: 16px 18px 16px 12px;">
                                        <div style="font-size: 13px; font-weight: 800; color: #0b1a30; margin-bottom: 3px;">
                                            Holiday affecting delivery
                                        </div>
                                        <div style="font-size: 12px; font-weight: 500; color: #78350f; line-height: 1.4;">
                                            {{ $holidayNotice ?? ($holidayReason . (!empty($holidayDate) ? ' · ' . $holidayDate : '')) }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Action Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td bgcolor="#CF5D38" style="border-radius: 8px;">
                                        <a href="{{ $actionUrl ?? '#' }}" style="display: inline-block; padding: 13px 28px; background-color: #CF5D38; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px;">
                                            {{ $actionText ?? 'View Order' }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Order Reference Footer -->
                            <p style="margin: 0; font-size: 13px; font-weight: 600; color: #64748b;">
                                Order #{{ $orderNumber ?? 'NP-12345' }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
