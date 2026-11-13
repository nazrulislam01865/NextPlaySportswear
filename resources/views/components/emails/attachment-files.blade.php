@props(['attachments' => [], 'fallbackUrl' => null])
@php
    // Only list real attachments or authorized download links. The email
    // transport still handles physical attachments; no file or DB reads here.
    $files = array_values(array_filter($attachments, static fn ($item): bool =>
        is_array($item) && filled($item['name'] ?? null)
        && in_array($item['type'] ?? '', ['data', 'storage', 'link'], true)));
@endphp
@if ($files !== [])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 25px;border-collapse:collapse;">
        <tr><td>
            <p style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:19px;line-height:1.45;font-weight:700;">Files included with this email</p>
            <p style="margin:11px 0 14px;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:14px;line-height:1.55;">The files associated with this message are listed below.</p>
            @foreach ($files as $file)
                @php
                    $filename = basename(str_replace('\\', '/', (string) $file['name']));
                    $extension = strtoupper(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'FILE';
                    $bytes = is_numeric($file['size'] ?? null)
                        ? (int) $file['size']
                        : (($file['type'] ?? '') === 'data' && is_string($file['data'] ?? null) ? strlen($file['data']) : null);
                    $size = $bytes !== null ? number_format(max(0, $bytes) / 1024) . ' KB' : null;
                    $isPdf = $extension === 'PDF';
                    // A general record URL is not a direct file download.
                    $download = $file['download_url'] ?? null;
                    $recordUrl = ($file['type'] ?? null) === 'link' ? $fallbackUrl : null;
                @endphp
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 9px;border:1px solid {{ config('transactional_email.design.divider') }};border-collapse:separate;border-spacing:0;border-radius:11px;">
                    <tr>
                        <td valign="middle" width="58" style="width:58px;padding:13px 0 13px 14px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;"><tr>
                                <td align="center" style="width:36px;height:40px;border:1px solid {{ $isPdf ? '#F3CDBE' : '#DCE5EF' }};border-radius:7px;background-color:{{ $isPdf ? '#FFF3EC' : '#EDF3FA' }};font-family:{{ config('transactional_email.design.font') }};font-size:12px;font-weight:700;color:{{ $isPdf ? '#D65632' : '#20527C' }};">{{ $extension }}</td>
                            </tr></table>
                        </td>
                        <td valign="middle" style="padding:12px 7px 12px 2px;min-width:0;">
                            <p style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:15px;font-weight:700;line-height:1.35;word-wrap:break-word;">{{ $filename }}</p>
                            <p style="margin:4px 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:12px;line-height:1.35;">{{ $extension }}{{ $size !== null ? ' · '.$size : '' }}</p>
                            @if (filled($file['description'] ?? null))
                                <p style="margin:4px 0 0;color:#547295;font-family:{{ config('transactional_email.design.font') }};font-size:12px;font-weight:700;line-height:1.35;">{{ $file['description'] }}</p>
                            @endif
                        </td>
                        <td align="right" valign="middle" width="84" style="width:84px;padding:12px 14px 12px 4px;">
                            @if (is_string($download) && preg_match('#^https?://#i', $download))
                                <a href="{{ $download }}" style="color:#D44D2B;font-family:{{ config('transactional_email.design.font') }};font-size:13px;line-height:1.4;text-decoration:underline;font-weight:700;">Download</a>
                            @elseif (is_string($recordUrl) && preg_match('#^https?://#i', $recordUrl))
                                <a href="{{ $recordUrl }}" style="color:#D44D2B;font-family:{{ config('transactional_email.design.font') }};font-size:13px;line-height:1.4;text-decoration:underline;font-weight:700;">View file</a>
                            @else
                                <span style="color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:12px;line-height:1.4;">Attached</span>
                            @endif
                        </td>
                    </tr>
                </table>
            @endforeach
        </td></tr>
    </table>
@endif
