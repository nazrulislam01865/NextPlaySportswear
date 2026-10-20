<x-layouts.storefront :seo="['title' => 'Request Changes to Quotation | NextPlay', 'robots' => 'noindex, nofollow']">
    @php($details = (array) $quote->quotation_details)
    <div class="np-gq-page np-gq-page--changes"><div class="np-gq-container">
        <nav class="np-gq-breadcrumb"><a href="{{ route('home') }}">Home</a> › <a href="{{ route('quote.guest.show', $quote->reference) }}">Track Bulk Quote</a> › {{ $quote->reference }} › Request Changes</nav>
        <div class="np-gq-changes-header"><div><h1>Request Changes to Your Quotation</h1><p>Send feedback on the quotation NextPlay prepared. No account is required.</p></div><span class="np-gq-pill">Guest access · Verified link</span></div>
        <div class="np-gq-verified"><strong>Secure quotation access</strong><small>Quote {{ $quote->reference }} · {{ $quote->email }}</small><span>Quotation Ready for Review</span></div>
        @if($errors->any())<div class="np-gq-alert np-gq-alert--error" role="alert">{{ $errors->first() }}</div>@endif
        <div class="np-gq-two-col">
            <div class="np-gq-stack">
                <x-storefront.bulk-quote.guest.panel title="Quotation being reviewed"><div class="np-gq-quote-snapshot"><small>Quote number</small><strong>{{ $quote->reference }}</strong><div class="np-gq-tags"><span>{{ number_format((int) ($details['quantity'] ?? 0)) }} pieces</span><span>{{ $quote->organization }}</span><span>USD {{ number_format((float) $quote->quoted_amount, 2) }}</span></div></div><dl class="np-gq-facts"><dt>Quotation sent by</dt><dd>NextPlay Sales</dd><dt>Last updated</dt><dd>{{ \App\Support\ViewerTime::render($quote->updated_at, 'M j, Y') }}</dd><dt>Valid until</dt><dd>{{ $quote->quotation_valid_until?->format('M j, Y') }}</dd><dt>Requested delivery</dt><dd>{{ $quote->needed_by?->format('M j, Y') }}</dd><dt>Current status</dt><dd>{{ str($quote->status)->headline() }}</dd></dl></x-storefront.bulk-quote.guest.panel>
                <x-storefront.bulk-quote.guest.panel title="Quotation files from NextPlay ({{ $files->count() }})" subtitle="These files were uploaded by NextPlay. View or download before requesting changes."><x-storefront.bulk-quote.guest.files :quote="$quote" :files="$files" compact /></x-storefront.bulk-quote.guest.panel>
                <x-storefront.bulk-quote.guest.messages :messages="$messages" :quote="$quote" />
            </div>
            <x-storefront.bulk-quote.guest.panel title="Request Quotation Changes" subtitle="Tell NextPlay which details should be revised. No order will be created by sending this request.">
                @if(in_array($quote->status, [\App\Models\BulkQuoteRequest::STATUS_ACCEPTED, \App\Models\BulkQuoteRequest::STATUS_CONVERTED, \App\Models\BulkQuoteRequest::STATUS_CLOSED]))
                    <p class="np-gq-muted">This quotation has been finalized. Please contact sales for assistance.</p>
                @else
                <form method="POST" action="{{ route('quote.guest.changes.send', $quote->reference) }}" enctype="multipart/form-data" class="np-gq-change-form">@csrf
                    <fieldset><legend>What would you like to change? <span>*</span></legend><p>Select all that apply.</p><div class="np-gq-check-grid">
                        @foreach(['pricing' => 'Pricing / quantity tiers', 'delivery' => 'Delivery date / shipping', 'products' => 'Products / quantities', 'customization' => 'Customization / artwork', 'payment' => 'Payment terms', 'other' => 'Other'] as $key => $label)
                            <label><input type="checkbox" name="change_types[]" value="{{ $key }}" @checked(in_array($key, old('change_types', [])))><span>{{ $label }}</span></label>
                        @endforeach
                    </div></fieldset>
                    <label class="np-gq-change-description">Describe your requested changes <span>*</span><textarea name="body" required minlength="5" maxlength="1000" rows="5" placeholder="Example: Please update the quotation for 30 pieces, confirm the best available unit price, and check whether delivery by April 30 is possible.">{{ old('body') }}</textarea><small>Maximum 1,000 characters</small></label>
                    <label class="np-gq-change-description">Attach supporting files (Optional)<span class="np-gq-upload np-gq-upload--large">Choose files to support your request<small>PDF, XLSX, XLS, PNG or JPG · Max 10 MB each</small><input type="file" name="files[]" multiple accept=".pdf,.xlsx,.xls,.png,.jpg,.jpeg"></span></label>
                    <p class="np-gq-blue-panel np-gq-info">NextPlay will review your changes and may upload a <strong>new quotation version</strong>. You can return to the same quote to review the update.</p>
                    <div class="np-gq-form-actions np-gq-form-actions--end"><a class="btn btn-white" href="{{ route('quote.guest.review', $quote->reference) }}">← Back to Quotation</a><button class="btn btn-red" type="submit">Send Change Request →</button></div>
                </form>
                @endif
            </x-storefront.bulk-quote.guest.panel>
        </div>
    </div></div>
</x-layouts.storefront>
