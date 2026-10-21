@props(['quote'])
@php
    $milestones = \App\Support\FlowTrackQuoteCustomerProgress::milestones($quote);
    $confirmed = in_array($quote->status, [\App\Models\BulkQuoteRequest::STATUS_ACCEPTED, \App\Models\BulkQuoteRequest::STATUS_CONVERTED], true);
    $steps = [
        ['label' => 'Quote Request Received', 'done' => true, 'date' => $quote->created_at, 'info' => 'We received your bulk quote request.'],
        ['label' => 'In Review', 'done' => $milestones['review'], 'date' => $milestones['review_at'], 'info' => 'Our team is reviewing your requirements.'],
        ['label' => 'Quotation Prepared', 'done' => $milestones['prepared'], 'date' => $milestones['prepared_at'], 'info' => 'Your offer has been prepared.'],
        ['label' => 'Quotation Ready', 'done' => $milestones['ready'], 'date' => $milestones['ready_at'], 'info' => 'Offer files are available for your review. Final terms may still be pending.'],
        ['label' => $confirmed ? 'Quotation Confirmed' : (\App\Support\FlowTrackQuoteCustomerProgress::published($quote) ? 'Awaiting Your Feedback' : 'Offer Shared'), 'done' => $confirmed || $milestones['feedback'], 'date' => $confirmed ? $quote->updated_at : $milestones['ready_at'], 'info' => $confirmed ? 'Thank you for confirming the quotation.' : (\App\Support\FlowTrackQuoteCustomerProgress::published($quote) ? 'We are waiting for your response.' : 'Your offer file has been shared. Final pricing and confirmation will follow from NextPlay Admin.')],
    ];
    $current = $confirmed ? 4 : ($milestones['ready'] ? 4 : ($milestones['prepared'] ? 2 : ($milestones['review'] ? 1 : 0)));
@endphp
<ol class="np-gq-timeline">
    @foreach($steps as $step)
        <li class="{{ $step['done'] ? 'done' : '' }} {{ $loop->index === $current ? 'current' : '' }}">
            <span class="np-gq-dot">{{ $step['done'] ? '✓' : '' }}</span>
            <div><strong>{{ $step['label'] }}</strong><small>{{ $step['done'] && $step['date'] ? \App\Support\ViewerTime::render($step['date'], 'M j, Y · g:i A') : '' }}</small><p>{{ $step['info'] }}</p></div>
        </li>
    @endforeach
</ol>
