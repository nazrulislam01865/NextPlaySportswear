@props(['logoUrl'])

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;table-layout:fixed;">
    <tr>
        <td align="left" style="padding:0 0 28px;">
            <img src="{{ $logoUrl }}" alt="{{ config('transactional_email.brand.name', 'NextPlay Sportswear') }}" width="320" style="display:block;width:auto;max-width:100%;max-height:52px;height:auto;border:0;outline:none;text-decoration:none;object-fit:contain;object-position:left center;" />
        </td>
    </tr>
    <tr>
        <td height="1" style="height:1px;line-height:1px;font-size:1px;border-top:1px solid {{ config('transactional_email.design.divider') }};">&nbsp;</td>
    </tr>
</table>
