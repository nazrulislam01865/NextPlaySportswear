@props(['proof'])
<article class="np-artwork-list-card">
    <a class="np-artwork-list-card__thumbnail" href="{{ route('account.artwork.show', $proof) }}" aria-label="Review proof for order {{ $proof->order->order_number }}">
        <x-storefront.account.artwork.preview :proof="$proof" compact />
    </a>
    <div class="np-artwork-list-card__details">
        <a class="np-artwork-order-link" href="{{ route('account.orders.show', $proof->order) }}">Order #{{ $proof->order->order_number }}</a>
        <h3>{{ $proof->orderItem->product_name }}</h3>
        <dl class="np-artwork-definition">
            <dt>Date Uploaded</dt><dd>{{ $proof->created_at?->format('M j, Y') }}</dd>
            <dt>Proof Version</dt><dd>v{{ $proof->version }}</dd>
            <dt>Items in Order</dt><dd>{{ $proof->quantityLabel() }}</dd>
            <dt>Design Name</dt><dd>{{ $proof->design_name }}</dd>
        </dl>
    </div>
    <div class="np-artwork-list-card__actions">
        <x-storefront.account.artwork.status :status="$proof->status" />
        @if($proof->status === 'pending_review')
            <p>Please review this proof and approve or request changes.</p>
            <a href="{{ route('account.artwork.show', $proof) }}" class="np-artwork-button np-artwork-button--primary">Review Proof <x-storefront.account.icon name="arrow-right" :size="18" /></a>
        @elseif($proof->status === 'approved')
            <p>You approved this design on <strong>{{ $proof->approved_at?->format('M j, Y') }}</strong>.<br>This artwork is ready for production.</p>
        @elseif($proof->status === 'needs_revision')
            <p>You requested changes on <strong>{{ $proof->revision_requested_at?->format('M j, Y') }}</strong>.<br>Our design team is working on your revisions.</p>
            <a href="{{ route('account.artwork.show', $proof) }}#proof-feedback" class="np-artwork-button">View Feedback</a>
        @else
            <p>You rejected this proof. The design team can review your feedback.</p>
            <a href="{{ route('account.artwork.show', $proof) }}#proof-feedback" class="np-artwork-button">View Feedback</a>
        @endif
        <div class="np-artwork-list-card__action-row">
            <a href="{{ route('account.artwork.file', $proof) }}?download=1" class="np-artwork-button"><x-storefront.account.icon name="downloads" :size="18" /> Download {{ $proof->mime_type === 'application/pdf' ? 'PDF' : 'Proof' }}</a>
            <a href="{{ route('account.artwork.show', $proof) }}?message=1#designer-messages" class="np-artwork-button"><x-storefront.account.icon name="message" :size="18" /> Message Designer</a>
        </div>
    </div>
</article>
