@php
    $details = $emailMessage->details;
    $previousEstimate = $details['Previous estimate'] ?? $details['Previous Estimate'] ?? null;
    $updatedEstimate = $details['Updated estimate'] ?? $details['Updated Estimate'] ?? $details['Estimated Delivery'] ?? null;
    $holidayReason = $details['Holiday affecting delivery'] ?? $details['Reason'] ?? null;
    $orderNumber = $details['Order number'] ?? $details['Order Number'] ?? null;
@endphp
<x-emails.notification-layout :email-message="$emailMessage" icon="notification-calendar">
    @foreach ($emailMessage->introLines as $line)
        @unless (preg_match('/^Hi\s+[^,]+,\s*$/iu', trim((string) $line)))
            <p class="np-mail-paragraph" style="margin:0 0 {{ $loop->last ? '0' : '13px' }};color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.5;">{{ $line }}</p>
        @endunless
    @endforeach

    @if (filled($previousEstimate) || filled($updatedEstimate))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:23px 0 20px;background-color:{{ config('transactional_email.design.soft') }};border-collapse:separate;border-spacing:0;border-radius:12px;table-layout:fixed;">
            <tr>
                <td width="50%" valign="top" style="padding:17px 26px;">
                    <p style="margin:0 0 4px;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:16px;line-height:1.4;">Previous estimate</p>
                    <p class="np-mail-estimate-value" style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.35;font-weight:700;">{{ $previousEstimate ?? '—' }}</p>
                </td>
                <td width="50%" valign="top" style="padding:17px 26px;">
                    <p style="margin:0 0 4px;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:16px;line-height:1.4;">Updated estimate</p>
                    <p class="np-mail-estimate-value" style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.35;font-weight:700;">{{ $updatedEstimate ?? '—' }}</p>
                </td>
            </tr>
        </table>
    @endif

    @if (filled($holidayReason))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 20px;border-collapse:separate;border-spacing:0;background-color:#FFF7E3;border-radius:12px;">
            <tr>
                <td width="55" valign="middle" style="padding:15px 0 15px 23px;">
                    <img src="{{ rtrim((string) config('app.url'), '/') }}/images/email/notification-holiday.png" alt="" aria-hidden="true" width="24" height="24" style="display:block;width:24px;height:24px;border:0;" />
                </td>
                <td valign="middle" style="padding:15px 19px 15px 10px;">
                    <p style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:19px;font-weight:700;line-height:1.35;">Holiday affecting delivery</p>
                    <p style="margin:3px 0 0;color:#A34316;font-family:{{ config('transactional_email.design.font') }};font-size:17px;line-height:1.35;">{{ $holidayReason }}</p>
                </td>
            </tr>
        </table>
    @endif

    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" :label="$emailMessage->actionText ?: 'View Order'" />
        </td></tr></table>
    @endif

    @if (filled($orderNumber))
        <p style="margin:19px 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:17px;line-height:1.5;">Order {{ str_starts_with((string) $orderNumber, '#') ? $orderNumber : '#'.$orderNumber }}</p>
    @endif
    @foreach ($emailMessage->outroLines as $line)
        <p class="np-mail-paragraph" style="margin:10px 0 0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:19px;line-height:1.5;">{{ $line }}</p>
    @endforeach
</x-emails.notification-layout>
