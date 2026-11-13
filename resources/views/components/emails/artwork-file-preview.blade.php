@props(['emailMessage', 'approved' => false, 'mailMessage' => null])
@php
    $file = collect($emailMessage->attachments)->first(fn ($item) =>
        is_array($item) && ($item['description'] ?? '') === 'Artwork proof');
    $version = $emailMessage->details['Proof Version'] ?? '';
    $order = $emailMessage->details['Order Number'] ?? '';
    $design = (string) ($emailMessage->metadata['artwork_design_name'] ?? '');
    $image = null;
    // Native Laravel mail embed is a private CID attachment, not a public URL.
    // Only image proofs can display previews; PDF files remain downloadable.
    if ($file && $mailMessage !== null && in_array($file['mime'] ?? '', ['image/png','image/jpeg','image/webp'], true)
        && ($file['disk'] ?? '') === 'local'
        && is_numeric($file['size'] ?? null) && (int) $file['size'] <= 2 * 1024 * 1024 && filled($file['path'] ?? null)
        && \Illuminate\Support\Facades\Storage::disk('local')->exists($file['path'])) {
        $image = $mailMessage->embed(\Illuminate\Support\Facades\Storage::disk('local')->path($file['path']));
    }
@endphp
@if ($file)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border:1px solid {{ config('transactional_email.design.divider') }};border-collapse:separate;border-spacing:0;border-radius:11px;background-color:{{ config('transactional_email.design.soft') }};margin:16px 0 0;">
        <tr>
            @if ($image)
                <td width="40%" align="center" valign="middle" style="padding:15px 0 15px 15px;width:40%;">
                    <img src="{{ $image }}" alt="{{ $approved ? 'Approved artwork' : 'Artwork proof' }}" width="140" style="display:block;max-width:100%;max-height:175px;width:auto;height:auto;border:0;" />
                </td>
            @endif
            <td valign="middle" style="padding:22px 16px;">
                <p style="margin:0;color:{{ config('transactional_email.design.navy') }};font-family:{{ config('transactional_email.design.font') }};font-size:19px;line-height:1.25;font-weight:700;">{{ $approved ? 'APPROVED' : 'ARTWORK PROOF' }}{{ filled($version) ? ' · '.$version : '' }}</p>
                @if (filled($design))<p style="margin:8px 0 0;color:{{ config('transactional_email.design.muted') }};font-size:14px;">{{ $design }}</p>@endif
                @if (filled($order))<p style="margin:3px 0 0;color:{{ config('transactional_email.design.muted') }};font-size:14px;">Order {{ $order }}</p>@endif
            </td>
        </tr>
    </table>
@endif
