@props(['details', 'labelWidth' => '38%'])
@if (!empty($details))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border:1px solid {{ config('transactional_email.design.divider') }};border-collapse:separate;border-spacing:0;border-radius:12px;overflow:hidden;table-layout:fixed;">
        @foreach ($details as $label => $value)
            <tr>
                <td class="np-mail-detail-cell np-mail-detail-label" valign="middle" width="{{ $labelWidth }}" style="width:{{ $labelWidth }};padding:11px 17px;background-color:{{ config('transactional_email.design.soft') }};border-bottom:{{ $loop->last ? '0' : '1px solid '.config('transactional_email.design.divider') }};color:#354b68;font-family:{{ config('transactional_email.design.font') }};font-size:17px;font-weight:700;line-height:1.35;word-wrap:break-word;">{{ $label }}</td>
                <td class="np-mail-detail-cell" valign="middle" width="{{ 100 - (int) $labelWidth }}%" style="width:{{ 100 - (int) $labelWidth }}%;padding:11px 17px;border-bottom:{{ $loop->last ? '0' : '1px solid '.config('transactional_email.design.divider') }};color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:17px;line-height:1.35;word-wrap:break-word;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>
@endif
