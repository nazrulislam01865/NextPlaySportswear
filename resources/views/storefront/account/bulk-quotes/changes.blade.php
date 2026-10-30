@php($details = (array) $quote->quotation_details)
<x-storefront.account.page :seo="$seo" title="Request Quotation Changes" subtitle="" :account="$account" :navigation="$navigation" content-only>
    <div class="np-rq np-rq--changes">
        <nav class="np-rq-breadcrumb"><a href="{{ route('account.dashboard') }}">My Account</a> › <a href="{{ route('account.bulk-quotes.index') }}">Bulk Quotes</a> › <a href="{{ route('account.bulk-quotes.show', $quote->reference) }}">{{ $quote->reference }}</a> › Request Changes</nav>
        <header class="np-rq-heading"><div><h1>Request Quotation Changes</h1><p>Ask NextPlay to revise pricing, product details, delivery or other quotation terms.</p></div><x-storefront.account.bulk-quote.status :quote="$quote" /></header>
        <div class="np-rq-change-grid">
            <div class="np-rq-column">
                <section class="np-rq-card"><div class="np-rq-snapshot-heading"><h2>Current NextPlay quotation</h2><span class="np-rq-version">Version {{ $quote->quotation_version }}</span></div>
                    <div class="np-rq-snapshot"><small>Quote number</small><strong>{{ $quote->reference }}</strong><span class="np-rq-snapshot-tags"><span>{{ number_format((int)($details['quantity'] ?? 0)) }} pieces</span><span>{{ $quote->organization ?: 'Team not specified' }}</span><span>{{ $quote->quote_currency ?: 'USD' }} {{ number_format((float)$quote->quoted_amount, 2) }}</span></span></div>
                    <dl class="np-rq-definition"><dt>Quotation sent by</dt><dd>NextPlay Sales</dd><dt>Last updated</dt><dd>{{ \App\Support\ViewerTime::render($quote->updated_at, 'M j, Y') }}</dd><dt>Valid until</dt><dd>{{ $quote->quotation_valid_until?->format('M j, Y') ?: '—' }}</dd><dt>Requested delivery</dt><dd>{{ $quote->needed_by?->format('M j, Y') ?: '—' }}</dd><dt>Current status</dt><dd><x-storefront.account.bulk-quote.status :quote="$quote" /></dd></dl>
                </section>
                <section class="np-rq-card"><h2>Quotation files from NextPlay ({{ $files->count() }})</h2><p class="np-rq-card-subtitle">These files were uploaded by NextPlay. View or download them before requesting changes.</p><x-storefront.account.bulk-quote.files :quote="$quote" :files="$files" mode="small" /></section>
                <x-storefront.account.bulk-quote.conversation :quote="$quote" :messages="$messages" />
            </div>
            <section class="np-rq-card np-rq-changes-form"><h2>Request Quotation Changes</h2><p class="np-rq-card-subtitle">Tell NextPlay which details should be revised. No order will be created by sending this request.</p>
                <form class="nps-form" method="POST" action="{{ route('account.bulk-quotes.changes.send', $quote->reference) }}" enctype="multipart/form-data" data-single-submit>
                    @csrf
                    <fieldset><legend>What would you like to change? <span class="np-rq-required">*</span></legend><p class="np-rq-card-subtitle">Select all that apply.</p>
                        <div class="np-rq-check-grid">
                            @foreach(['pricing' => 'Pricing / quantity tiers', 'delivery' => 'Delivery date / shipping', 'products' => 'Products / quantities', 'customization' => 'Customization / artwork', 'payment' => 'Payment terms', 'other' => 'Other'] as $key => $label)
                                <label><input type="checkbox" name="change_types[]" value="{{ $key }}" @checked(in_array($key, old('change_types', [])))><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        @error('change_types')<p class="np-rq-error" role="alert">{{ $message }}</p>@enderror
                    </fieldset>
                    <label for="np-rq-changes-body" class="np-rq-label">Describe your requested changes <span class="np-rq-required">*</span></label>
                    <textarea id="np-rq-changes-body" name="body" rows="5" minlength="5" maxlength="1000" required placeholder="Example: Please update the quotation for 30 pieces and confirm the best unit price and available delivery date.">{{ old('body') }}</textarea>
                    <small class="np-rq-counter">Maximum 1,000 characters</small>
                    @error('body')<p class="np-rq-error" role="alert">{{ $message }}</p>@enderror
                    <label class="np-rq-label" for="np-rq-changes-files">Attach supporting files <span class="np-rq-muted">(Optional)</span></label>
                    <div class="np-rq-drop"><strong>Choose files to support your request</strong><small>PDF, XLSX, XLS, PNG or JPG · Max 10 MB each</small><input id="np-rq-changes-files" type="file" name="files[]" accept=".pdf,.xlsx,.xls,.png,.jpg,.jpeg" multiple></div>
                    @error('files')<p class="np-rq-error" role="alert">{{ $message }}</p>@enderror
                    <p class="np-rq-info">NextPlay will review your changes and may upload a <strong>new quotation version.</strong> You can return to the same quote to review the update.</p>
                    <div class="np-rq-form-end"><a class="btn btn-white" href="{{ route('account.bulk-quotes.show', $quote->reference) }}">← Back to Quotation</a><button type="submit" class="btn btn-red">Send Change Request →</button></div>
                </form>
            </section>
        </div>
    </div>
</x-storefront.account.page>
