@props(['supportUrl'])

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;table-layout:fixed;">
    <tr>
        <td height="1" style="height:1px;line-height:1px;font-size:1px;border-top:1px solid {{ config('transactional_email.design.divider') }};">&nbsp;</td>
    </tr>
    <tr>
        <td style="padding:18px 0 0;font-family:{{ config('transactional_email.design.font') }};font-size:16px;line-height:1.55;color:{{ config('transactional_email.design.muted') }};">
            Need help? <a href="{{ $supportUrl }}" style="color:{{ config('transactional_email.design.navy') }};font-size:16px;text-decoration:underline;">Contact support.</a>
        </td>
    </tr>
    <tr>
        <td style="padding-top:3px;font-family:{{ config('transactional_email.design.font') }};font-size:16px;line-height:1.55;color:{{ config('transactional_email.design.muted') }};">NextPlay Sportswear</td>
    </tr>
</table>
