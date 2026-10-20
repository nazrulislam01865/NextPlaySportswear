@props(['quote'])
@php
    $sent = $quote->quotation_version > 0 && $quote->quotation_sent_at;
    $steps = [
        ['label' => 'Quote Request Received', 'done' => true, 'date' => $quote->created_at, 'info' => 'We received your bulk quote request.'],
        ['label' => 'In Review', 'done' => $quote->status !== \App\Models\BulkQuoteRequest::STATUS_NEW, 'date' => $quote->updated_at, 'info' => 'Our team is reviewing your requirements.'],
        ['label' => 'Quotation Prepared', 'done' => $sent, 'date' => $quote->quotation_sent_at, 'info' => 'Your quotation has been created.'],
        ['label' => 'Quotation Ready', 'done' => $sent, 'date' => $quote->quotation_sent_at, 'info' => 'Quotation files are available for your review.'],
        ['label' => $quote->status === \App\Models\BulkQuoteRequest::STATUS_ACCEPTED ? 'Quotation Confirmed' : 'Awaiting Your Feedback', 'done' => in_array($quote->status, [\App\Models\BulkQuoteRequest::STATUS_ACCEPTED, \App\Models\BulkQuoteRequest::STATUS_CUSTOMER_RESPONDED]), 'date' => $quote->status === \App\Models\BulkQuoteRequest::STATUS_ACCEPTED ? $quote->updated_at : null, 'info' => $quote->status === \App\Models\BulkQuoteRequest::STATUS_ACCEPTED ? 'Thank you for confirming the quotation.' : 'We are waiting for your response.'],
    ];
@endphp
<ol class="np-gq-timeline">
    @foreach($steps as $step)
        <li class="{{ $step['done'] ? 'done' : '' }} {{ $loop->index === ($sent ? 3 : 0) ? 'current' : '' }}">
            <span class="np-gq-dot">{{ $step['done'] ? '✓' : '' }}</span>
            <div><strong>{{ $step['label'] }}</strong><small>{{ $step['done'] && $step['date'] ? \App\Support\ViewerTime::render($step['date'], 'M j, Y · g:i A') : '' }}</small><p>{{ $step['info'] }}</p></div>
        </li>
    @endforeach
</ol>
