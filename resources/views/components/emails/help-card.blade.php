@props(['title' => 'Need help?', 'description' => 'Contact our support team and we’ll be happy to assist you.'])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:separate;border-spacing:0;background-color:{{ config('transactional_email.design.soft') }};border-radius:10px;">
    <tr>
        <td style="padding:20px 24px;">
            <p style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;font-weight:700;line-height:1.35;">{{ $title }}</p>
            <p style="margin:6px 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:18px;line-height:1.45;">{{ $description }}</p>
        </td>
    </tr>
</table>
