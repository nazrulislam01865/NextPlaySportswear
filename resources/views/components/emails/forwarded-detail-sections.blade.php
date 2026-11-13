@props(['sections' => []])
@if ($sections !== [])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;margin:25px 0 0;">
        <tr><td>
            <p style="margin:0 0 11px;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:19px;font-weight:700;line-height:1.4;">Original request details</p>
            @foreach ($sections as $section)
                @if (!empty($section['rows']))
                    <p style="margin:19px 0 8px;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:16px;font-weight:700;line-height:1.4;">{{ $section['title'] }}</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border:1px solid {{ config('transactional_email.design.divider') }};border-collapse:collapse;table-layout:fixed;">
                        @foreach ($section['rows'] as $row)
                            @if ($row['group'])
                                <tr>
                                    <td colspan="2" style="padding:10px 13px 10px {{ 13 + min(8, (int) $row['depth']) * 10 }}px;background:{{ config('transactional_email.design.soft') }};border-bottom:1px solid {{ config('transactional_email.design.divider') }};color:{{ config('transactional_email.design.navy') }};font-size:13px;font-family:{{ config('transactional_email.design.font') }};font-weight:700;word-wrap:break-word;">{{ $row['label'] }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td valign="top" width="39%" style="width:39%;padding:10px 12px 10px {{ 12 + min(8, (int) $row['depth']) * 10 }}px;background:{{ config('transactional_email.design.soft') }};border-bottom:1px solid {{ config('transactional_email.design.divider') }};color:#354b68;font-family:{{ config('transactional_email.design.font') }};font-size:13px;font-weight:700;line-height:1.5;word-wrap:break-word;">{{ $row['label'] }}</td>
                                    <td valign="top" style="padding:10px 12px;border-bottom:1px solid {{ config('transactional_email.design.divider') }};color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:13px;line-height:1.6;word-wrap:break-word;">{!! nl2br(e($row['value'])) !!}</td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                @endif
            @endforeach
        </td></tr>
    </table>
@endif
