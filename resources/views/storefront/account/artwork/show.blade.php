<x-storefront.account.page :seo="$seo" title="Review Artwork Proof" subtitle="" :account="$account" :navigation="$navigation" content-only>
    <div class="np-artwork">
        <x-storefront.account.artwork.breadcrumb :order="$proof" />
        <header class="np-artwork-heading">
            <h1>Review Artwork Proof</h1>
            <p>Review the latest proof carefully before approving production.</p>
        </header>
        <div class="np-artwork-detail-grid">
            <div class="np-artwork-detail-main">
                <section class="np-artwork-panel np-artwork-proof-panel">
                    <div class="np-artwork-proof-toolbar">
                        <div class="np-artwork-version-tabs">
                            <span class="np-artwork-version-active">Version v{{ $proof->version }}</span>
                            @if($versions->count() > 1)
                                <details class="np-artwork-version-menu"><summary>Previous Versions ({{ $versions->count() - 1 }})</summary>
                                    <div class="np-artwork-version-menu__list">
                                        @foreach($versions as $version)
                                            @if($version->id !== $proof->id)<a href="{{ route('account.artwork.show', $version) }}">Version v{{ $version->version }} · {{ $version->created_at?->format('M j, Y') }}</a>@endif
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </div>
                        <div class="np-artwork-proof-tools">
                            <a href="{{ route('account.artwork.file', $proof) }}" target="_blank" rel="noopener"><x-storefront.account.icon name="zoom-in" :size="18" /> Zoom In</a>
                            <a href="{{ route('account.artwork.file', $proof) }}?download=1"><x-storefront.account.icon name="downloads" :size="18" /> Download</a>
                            <a href="{{ route('account.artwork.file', $proof) }}" target="_blank" rel="noopener" aria-label="View fullscreen"><x-storefront.account.icon name="expand" :size="18" /></a>
                        </div>
                    </div>
                    <x-storefront.account.artwork.preview :proof="$proof" class="np-artwork-proof-large" />
                    <div class="np-artwork-download-banner"><x-storefront.account.icon name="downloads" :size="21" />
                        <span><strong>Proof files available:</strong> Download {{ $proof->mime_type === 'application/pdf' ? 'PDF' : 'image' }} to view full resolution.</span>
                        <a class="np-artwork-button" href="{{ route('account.artwork.file', $proof) }}?download=1">Download {{ $proof->mime_type === 'application/pdf' ? 'PDF' : 'Image' }}</a>
                    </div>
                </section>
                <section class="np-artwork-panel np-artwork-note-panel">
                    <div class="np-artwork-panel-title"><x-storefront.account.icon name="message" :size="24" /><h2>Designer Notes</h2><span>{{ $proof->created_at?->format('M j, Y') }}</span><b>v{{ $proof->version }}</b></div>
                    <p>{{ $proof->designer_notes ?: 'Please review the attached artwork proof and let us know if any adjustments are needed.' }}</p>
                </section>
                <section class="np-artwork-panel np-artwork-history-panel">
                    <div class="np-artwork-panel-title"><x-storefront.account.icon name="refresh" :size="24" /><h2>Revision History</h2></div>
                    <ol class="np-artwork-history">
                        @foreach($versions as $version)
                            <li class="{{ $version->id === $proof->id ? 'is-current' : '' }}">
                                <span class="np-artwork-history-dot"></span>
                                <b>v{{ $version->version }}</b>
                                <div><span>{{ $version->created_at?->format('M j, Y · g:i A') }}</span><p>{{ $version->designer_notes ?: 'Artwork proof uploaded for review.' }}</p></div>
                                @if($version->id === $versions->first()->id)<small>Current Version</small>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
                @if($proof->customer_feedback)
                    <section class="np-artwork-panel np-artwork-feedback" id="proof-feedback">
                        <div class="np-artwork-panel-title"><x-storefront.account.icon name="message" :size="24" /><h2>Your Feedback</h2></div>
                        <p>{{ $proof->customer_feedback }}</p>
                        @if($proof->reference_path)<a href="{{ route('account.artwork.file', $proof) }}?type=reference&amp;download=1" class="np-artwork-button">Download Your Reference File</a>@endif
                    </section>
                @endif
                <section id="designer-messages" class="np-artwork-panel np-artwork-messages" aria-label="Messages with design team">
                    <div class="np-artwork-panel-title np-artwork-messages__heading">
                        <x-storefront.account.icon name="message" :size="22" />
                        <h2>Messages with Design Team</h2>
                        @if($messages->isNotEmpty())<span>{{ $messages->count() }} {{ \Illuminate\Support\Str::plural('message', $messages->count()) }}</span>@endif
                    </div>
                    <div class="np-artwork-messages__body">
                        @forelse($messages as $message)
                            <div class="np-artwork-message {{ $message->sender_role === 'designer' ? 'is-designer' : 'is-customer' }}">
                                <div class="np-artwork-message__meta"><strong>{{ $message->sender_role === 'designer' ? 'Design Team' : 'You' }}</strong><small>{{ $message->created_at?->format('M j, Y · g:i A') }}</small></div>
                                <p>{{ $message->message }}</p>
                            </div>
                        @empty
                            <p class="np-artwork-messages__empty">No messages yet. Questions or updates about your proof will appear here.</p>
                        @endforelse
                        <form method="POST" action="{{ route('account.artwork.message', $proof) }}">@csrf
                            <label for="designer-message">Message the design team</label>
                            <textarea id="designer-message" name="message" minlength="5" maxlength="2000" required rows="3" placeholder="Ask a question or reply to the design team...">{{ old('message') }}</textarea>
                            @error('message')<p role="alert" class="np-artwork-form-error">{{ $message }}</p>@enderror
                            <button class="np-artwork-button np-artwork-button--primary" type="submit">Send Message</button>
                        </form>
                    </div>
                </section>
            </div>
            <aside class="np-artwork-detail-side">
                <section class="np-artwork-panel np-artwork-order-summary">
                    <div class="np-artwork-order-summary__head"><h2>Order #{{ $proof->order->order_number }}</h2><x-storefront.account.artwork.status :status="$proof->status" /></div>
                    <div class="np-artwork-order-summary__product"><x-storefront.account.artwork.preview :proof="$proof" compact /><strong>{{ $proof->orderItem->product_name }}</strong></div>
                    <dl class="np-artwork-definition"><dt>Quantity</dt><dd>{{ $proof->quantityLabel() }}</dd><dt>Submitted Date</dt><dd>{{ $proof->created_at?->format('M j, Y') }}</dd><dt>Design Name</dt><dd>{{ $proof->design_name }}</dd><dt>Current Version</dt><dd>v{{ $versions->first()->version }}</dd></dl>
                </section>
                <section class="np-artwork-panel np-artwork-review-checklist">
                    <h2>Review Checklist</h2><p>Please review the following details to ensure everything is correct.</p>
                    <ul>
                        @foreach(['Colors match your request', 'Logo placement and size', 'Names and numbers (spelling & style)', 'Sizing and layout', 'Sponsor details (if applicable)'] as $check)
                            <li><x-storefront.account.icon name="check-circle" :size="19" />{{ $check }}</li>
                        @endforeach
                    </ul>
                    @if($isCurrent && $proof->status === 'pending_review')
                        <form method="POST" action="{{ route('account.artwork.approve', $proof) }}" onsubmit="return confirm('Approve this artwork version for production? This action cannot be undone.');">@csrf
                            <input type="hidden" name="confirm" value="1">
                            <button type="submit" class="np-artwork-button np-artwork-button--primary np-artwork-button--block"><x-storefront.account.icon name="check-circle" :size="20" /> Approve Artwork</button>
                        </form>
                        <a class="np-artwork-button np-artwork-button--block" href="{{ route('account.artwork.changes', $proof) }}"><x-storefront.account.icon name="message" :size="20" /> Request Changes</a>
                        <details class="np-artwork-reject"><summary class="np-artwork-button np-artwork-button--reject np-artwork-button--block"><x-storefront.account.icon name="alert" :size="20" /> Reject Proof</summary>
                            <form method="POST" action="{{ route('account.artwork.reject', $proof) }}">@csrf
                                <label for="reject-reason">Reason for rejection *</label>
                                <textarea id="reject-reason" name="feedback" required minlength="10" maxlength="500" rows="3" placeholder="Explain why you cannot accept this proof."></textarea>
                                <button class="np-artwork-button np-artwork-button--reject np-artwork-button--block" type="submit">Confirm Rejection</button>
                            </form>
                        </details>
                        <p class="np-artwork-aside-notice"><x-storefront.account.icon name="info" :size="20" /> Approving this artwork confirms your design. Production can begin once the remaining order requirements are met.</p>
                    @else
                        <div class="np-artwork-review-state"><x-storefront.account.artwork.status :status="$proof->status" /><p>{{ $isCurrent ? 'This proof has already been reviewed.' : 'You are viewing an earlier proof version.' }}</p></div>
                        @if($proof->status === 'approved')<a class="np-artwork-button np-artwork-button--block" href="{{ route('account.artwork.approved', $proof) }}">View Approval Confirmation</a>@endif
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-storefront.account.page>
