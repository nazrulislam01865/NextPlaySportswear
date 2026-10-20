<x-layouts.storefront :seo="['title' => 'Track Bulk Quote | NextPlay', 'robots' => 'noindex, nofollow']">
    <div class="np-gq-page np-gq-page--track"><div class="np-gq-container">
        <header class="np-gq-page-head"><h1>Track Bulk Quote</h1><p>Enter your quote number and email address to view the status and quotation files.</p></header>
        <form class="np-gq-panel np-gq-search" method="POST" action="{{ route('quote.guest.lookup') }}" data-guest-quote-lookup novalidate>
            @csrf
            <label for="guest-quote-reference">
                <span class="np-gq-label-line">Quote Number <span aria-hidden="true">*</span></span>
                <input id="guest-quote-reference" name="reference" value="{{ old('reference', $quote?->reference) }}" placeholder="BQ-2024-1018" required maxlength="32" autocomplete="off" aria-required="true" aria-invalid="{{ $errors->has('reference') ? 'true' : 'false' }}" aria-describedby="guest-quote-reference-error">
                <span class="np-gq-field-error" id="guest-quote-reference-error" role="alert" aria-live="polite">@error('reference'){{ $message }}@enderror</span>
            </label>
            <label for="guest-quote-email">
                <span class="np-gq-label-line">Email Address <span aria-hidden="true">*</span></span>
                <input id="guest-quote-email" type="email" name="email" value="{{ old('email', $quote?->email) }}" placeholder="team@example.com" required maxlength="190" autocomplete="email" aria-required="true" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="guest-quote-email-error">
                <span class="np-gq-field-error" id="guest-quote-email-error" role="alert" aria-live="polite">@error('email'){{ $message }}@enderror</span>
            </label>
            <button class="btn btn-red" type="submit"><x-storefront.bulk-quote.guest.icon name="search" size="23" />Track Quote</button>
        </form>
        <script>
            (() => {
                const form = document.querySelector('[data-guest-quote-lookup]');
                if (!form) return;
                const fields = [form.elements.namedItem('reference'), form.elements.namedItem('email')];
                const update = (field, message) => {
                    document.getElementById(field.id + '-error').textContent = message;
                    field.setAttribute('aria-invalid', message ? 'true' : 'false');
                };
                const validate = (field) => {
                    const value = field.value.trim();
                    if (!value) return field.name === 'reference' ? 'Please enter your quote number.' : 'Please enter your email address.';
                    if (field.name === 'email' && !field.validity.valid) return 'Please enter a valid email address.';
                    return '';
                };
                fields.forEach((field) => {
                    field.addEventListener('input', () => {
                        if (field.getAttribute('aria-invalid') === 'true' || form.dataset.submitted === 'true') {
                            update(field, validate(field));
                        }
                    });
                    field.addEventListener('blur', () => {
                        if (field.value.trim() || form.dataset.submitted === 'true') update(field, validate(field));
                    });
                });
                form.addEventListener('submit', (event) => {
                    form.dataset.submitted = 'true';
                    let firstInvalid = null;
                    fields.forEach((field) => {
                        const message = validate(field);
                        update(field, message);
                        if (message && !firstInvalid) firstInvalid = field;
                    });
                    if (firstInvalid) {
                        event.preventDefault();
                        firstInvalid.focus();
                    }
                });
            })();
        </script>
        @if(session('status'))<div class="np-gq-alert" role="status">{{ session('status') }}</div>@endif
        @if($quote)
            @php
                $ready = $quote->quotation_version > 0 && $quote->quotation_sent_at;
                $accepted = in_array($quote->status, [\App\Models\BulkQuoteRequest::STATUS_ACCEPTED, \App\Models\BulkQuoteRequest::STATUS_CONVERTED], true);
                $responded = $quote->status === \App\Models\BulkQuoteRequest::STATUS_CUSTOMER_RESPONDED;
                $statusTitle = $accepted ? 'Quotation Confirmed' : ($quote->guest_decision === 'changes_requested' ? 'Changes Requested' : ($ready ? 'Quotation Ready' : (\App\Models\BulkQuoteRequest::statuses()[$quote->status] ?? 'Under Review')));
            @endphp
            <div class="np-gq-track-grid">
                <div class="np-gq-main-column">
                    <div class="np-gq-status np-gq-panel">
                        <span class="np-gq-success-check"><x-storefront.bulk-quote.guest.icon :name="$ready ? 'check' : 'clock'" size="43" /></span>
                        <div class="np-gq-status-text"><small>CURRENT STATUS</small><h2>{{ $statusTitle }}</h2><p>{{ $accepted ? 'Thank you. Your quotation has been confirmed.' : ($quote->guest_decision === 'changes_requested' ? 'Our team will review your requested changes.' : ($ready ? 'Your bulk quote has been prepared and is ready for review.' : 'Our team is preparing your custom quotation.')) }}</p></div>
                        @if($ready)<div class="np-gq-status-notice"><strong><x-storefront.bulk-quote.guest.icon name="bell" size="19" /> New Update</strong><p>Quotation files are available.</p><small>{{ \App\Support\ViewerTime::render($quote->quotation_sent_at, 'M j · g:i A') }}</small></div>@endif
                    </div>
                    <x-storefront.bulk-quote.guest.panel title="Quote Details">
                        <div class="np-gq-detail-grid">
                            <div><small>Quote Number</small><strong><x-storefront.bulk-quote.guest.icon name="file" size="20" /> {{ $quote->reference }}</strong></div>
                            <div><small>Customer Email</small><strong><x-storefront.bulk-quote.guest.icon name="mail" size="20" /> {{ $quote->email }}</strong></div>
                            <div><small>Request Date</small><strong><x-storefront.bulk-quote.guest.icon name="calendar" size="20" /> {{ \App\Support\ViewerTime::render($quote->created_at, 'M j, Y') }}</strong></div>
                            <div><small>Estimated Valid Until</small><strong><x-storefront.bulk-quote.guest.icon name="calendar" size="20" /> {{ $quote->quotation_valid_until?->format('M j, Y') ?: 'Pending quotation' }}</strong></div>
                        </div>
                    </x-storefront.bulk-quote.guest.panel>
                    <x-storefront.bulk-quote.guest.panel title="Quotation Files" subtitle="Download and review the files shared by our team.">
                        @if($ready)<div class="np-gq-file-info"><x-storefront.bulk-quote.guest.icon name="shield" size="19" /><span>These files have been prepared by NextPlay Sportswear based on your request. Please review and let us know if you have any questions or need revisions.</span></div>@endif
                        <x-storefront.bulk-quote.guest.files :quote="$quote" :files="$files" />
                    </x-storefront.bulk-quote.guest.panel>
                    <x-storefront.bulk-quote.guest.messages :messages="$messages" :quote="$quote" />
                </div>
                <aside class="np-gq-aside">
                    <x-storefront.bulk-quote.guest.panel title="Quote Timeline"><x-storefront.bulk-quote.guest.timeline :quote="$quote" /></x-storefront.bulk-quote.guest.panel>
                    <x-storefront.bulk-quote.guest.panel title="Need Help?" subtitle="Our sales team is here to assist you with any questions about your quotation." icon="headphones"><a class="btn btn-white np-gq-wide" href="{{ route('contact') }}"><x-storefront.bulk-quote.guest.icon name="message" size="19" /> Contact Sales</a></x-storefront.bulk-quote.guest.panel>
                </aside>
            </div>
            @if($ready)
                <div class="np-gq-cta-grid"><a class="btn btn-red" href="{{ route('quote.guest.review', $quote->reference) }}"><x-storefront.bulk-quote.guest.icon name="file" size="23" /><span>Review Quotation<small>Download and review all files</small></span></a><a class="btn btn-white" href="{{ route('quote.guest.changes', $quote->reference) }}"><x-storefront.bulk-quote.guest.icon name="message" size="23" /><span>Send Feedback<small>Let us know your thoughts or request changes</small></span></a><a class="btn btn-white" href="{{ route('contact') }}"><x-storefront.bulk-quote.guest.icon name="headphones" size="23" /><span>Contact Sales<small>Get help from our team</small></span></a></div>
            @endif
        @endif
    </div></div>
</x-layouts.storefront>
