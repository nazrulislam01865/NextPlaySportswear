@props(['url', 'label'])

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;margin:0;">
    <tr>
        <td align="center" bgcolor="{{ config('transactional_email.design.orange') }}" style="background-color:{{ config('transactional_email.design.orange') }};background-image:linear-gradient(90deg,#D85A36,{{ config('transactional_email.design.orange') }});border-radius:9px;">
            <a href="{{ $url }}" style="display:block;padding:19px 47px;min-width:176px;text-align:center;background-color:{{ config('transactional_email.design.orange') }};border-radius:9px;color:#ffffff;font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.3;font-weight:700;text-decoration:none !important;white-space:nowrap;">{{ $label }}</a>
        </td>
    </tr>
</table>
