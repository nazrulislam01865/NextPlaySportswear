@php
    // The forwarded message already contains the source data. Select the
    // reference design using those existing fields; no extra DB query.
    $original = $emailMessage->details;
    $isOrder = array_key_exists('Order number', $original);
    $isQuote = ! $isOrder && array_key_exists('Request reference', $original);
    $attachments = $emailMessage->attachments ?? [];
    $hasOriginalFile = collect($attachments)->contains(static fn ($file): bool =>
        is_array($file) && !str_ends_with((string) ($file['name'] ?? ''), '_Details.txt'));

    if ($isOrder) {
        $icon = 'notification-order-received';
        $heading = 'Forwarded order confirmation';
        $intro = 'A new order confirmation has been forwarded with the original order details.';
        $rows = [
            'Order Number' => $original['Order number'] ?? null,
            'Customer' => $original['Customer name'] ?? null,
            'Items' => $original['Items'] ?? null,
            'Total' => $original['Grand total'] ?? null,
            'Attachment' => $hasOriginalFile ? 'Original invoice PDF' : null,
        ];
        $action = 'Review Order';
    } elseif ($isQuote) {
        $icon = 'notification-bulk-quote';
        $heading = 'Forwarded quote request';
        $intro = $hasOriginalFile
            ? 'A new quotation request has been forwarded with the original attachment.'
            : 'A new quotation request has been forwarded for review.';
        $rows = [
            'Quotation Reference' => $original['Request reference'] ?? null,
            'Customer' => $original['Customer name'] ?? null,
            'Product' => $original['Product'] ?? null,
            'Quantity' => $original['Quantity'] ?? null,
            'Attachment' => $hasOriginalFile ? 'Customer reference file' : null,
        ];
        $action = 'Review Quotation';
    } else {
        $icon = 'notification-envelope';
        $heading = 'Forwarded contact message';
        $intro = 'A customer contact submission has been forwarded for your review.';
        $rows = [
            'Name' => $original['Customer name'] ?? null,
            'Email' => $original['Customer email'] ?? null,
            'Topic' => $original['Subject'] ?? null,
            'Order Number' => $original['Order / quote number'] ?? null,
            'Attachment' => $hasOriginalFile || filled($original['Original attachment'] ?? null) ? 'Customer uploaded file' : null,
        ];
        $action = 'Review Message';
    }
    $rows = array_filter($rows, static fn ($value): bool => filled($value));
@endphp
<x-emails.notification-layout :email-message="$emailMessage" :icon="$icon" :heading="$heading" :internal="true">
    <p class="np-mail-paragraph" style="margin:0;color:{{ config('transactional_email.design.text') }};font-family:{{ config('transactional_email.design.font') }};font-size:20px;line-height:1.55;">{{ $intro }}</p>

    @if ($rows !== [])
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 0;border-collapse:collapse;"><tr><td>
            <x-emails.details-table :details="$rows" label-width="43%" />
        </td></tr></table>
    @endif

    <x-emails.attachment-files :attachments="$attachments" />

    {{-- Keep the complete nested product, customization, customer and shipping
         information that original forwarded messages already contained. --}}
    <x-emails.forwarded-detail-sections :sections="$emailMessage->sections" />

    @foreach ($emailMessage->introLines as $line)
        @if (filled($line))
            <p style="margin:19px 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:14px;line-height:1.55;">{!! nl2br(e($line)) !!}</p>
        @endif
    @endforeach

    @foreach ($emailMessage->outroLines as $line)
        @if (filled($line))
            <p style="margin:10px 0 0;color:{{ config('transactional_email.design.muted') }};font-family:{{ config('transactional_email.design.font') }};font-size:13px;line-height:1.5;">{{ $line }}</p>
        @endif
    @endforeach

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:27px 0 0;"><tr><td>
        <x-emails.help-card />
    </td></tr></table>
    @if (filled($emailMessage->actionUrl))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;"><tr><td>
            <x-emails.action-button :url="$emailMessage->actionUrl" :label="$action" />
        </td></tr></table>
    @endif
</x-emails.notification-layout>
