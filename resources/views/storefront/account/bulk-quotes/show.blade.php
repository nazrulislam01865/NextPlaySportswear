@php
    $details = (array) $quote->quotation_details;
    $ready = $quote->quotation_version > 0 && $quote->quotation_sent_at;
    $priced = !($details['pricing_pending'] ?? false) && (int) ($details['quantity'] ?? 0) > 0 && (float) $quote->quoted_amount > 0;
    $confirmed = in_array($quote->status, ['accepted','converted'], true);
    $final = $confirmed || in_array($quote->status, ['closed','rejected'], true);
    $expired = $quote->quotation_valid_until && $quote->quotation_valid_until->lt(today());
    $canConfirm = $ready && !$final && !$expired && $quote->guest_decision !== 'changes_requested';
    $canReply = ($ready || $quote->guest_decision === 'changes_requested') && !$final;
@endphp
<x-storefront.account.page :seo="$seo" title="Quotation Details" subtitle="" :account="$account" :navigation="$navigation" content-only>
    <div class="np-rq np-rq--detail">
        <nav class="np-rq-breadcrumb"><a href="{{ route('account.dashboard') }}">Account</a> › <a href="{{ route('account.bulk-quotes.index') }}">Bulk Quotes</a> › {{ $quote->reference }}</nav>
        <header class="np-rq-heading np-rq-heading--detail">
            <div><h1>Quotation Details</h1><p>Review the quotation, download files, and respond to our team.</p></div>
            <div class="np-rq-detail-status"><x-storefront.account.bulk-quote.status :quote="$quote" /><small>
                    @if($ready)
                        Sent on {{ \App\Support\ViewerTime::render($quote->quotation_sent_at, 'M j, Y · g:i A') }}
                    @else
                        Submitted {{ \App\Support\ViewerTime::render($quote->created_at, 'M j, Y') }}
                    @endif
                    @if($ready) · Version {{ $quote->quotation_version }} @endif
                </small></div>
        </header>
        <div class="np-rq-detail-columns">
            <div class="np-rq-column">
                <section class="np-rq-card">
                    <h2>Quotation Summary</h2>
                    <dl class="np-rq-definition">
                        <dt>Quote Number</dt><dd>{{ $quote->reference }}</dd>
                        <dt>Team / Organization</dt><dd>{{ $quote->organization ?: '—' }}</dd>
                        <dt>Submitted Date</dt><dd>{{ \App\Support\ViewerTime::render($quote->created_at, 'M j, Y') }}</dd>
                        <dt>Valid Until</dt><dd>{{ $quote->quotation_valid_until?->format('M j, Y') ?: ($ready ? 'See attached offer' : 'Awaiting quotation') }} @if($expired)<span class="np-rq-warning">(Expired)</span>@endif</dd>
                        <dt>Status</dt><dd><x-storefront.account.bulk-quote.status :quote="$quote" /></dd>
                        <dt>Version</dt><dd>v{{ $quote->quotation_version ?: '0' }}</dd>
                        <dt>Required Delivery</dt><dd>{{ $quote->needed_by?->format('M j, Y') ?: 'To be confirmed' }}</dd>
                        <dt>Products</dt><dd>{{ $quote->product_type }}</dd>
                        <dt>Special Notes</dt><dd>{{ $quote->additional_notes ?: ($quote->artwork_details ?: '—') }}</dd>
                    </dl>
                </section>
                <section class="np-rq-card">
                    <h2>Pricing Summary</h2>
                    @if($ready && !$priced)
                        <p class="np-rq-muted">Your formal quotation is attached. Please refer to the offer document for pricing. A structured checkout amount has not been entered, so an order cannot yet be created online.</p>
                    @elseif($ready)
                        <table class="np-rq-pricing"><thead><tr><th>Description</th><th>Amount ({{ $quote->quote_currency ?: 'USD' }})</th></tr></thead><tbody>
                            <tr><td>Products ({{ number_format((int) ($details['quantity'] ?? 0)) }} pieces)</td><td>{{ number_format((float) ($details['subtotal'] ?? 0), 2) }}</td></tr>
                            <tr><td>Customization Fees</td><td>{{ number_format((float) ($details['customization'] ?? 0), 2) }}</td></tr>
                            <tr><td>Estimated Shipping</td><td>{{ number_format((float) ($details['shipping'] ?? 0), 2) }}</td></tr>
                            <tr class="np-rq-total"><th>Total ({{ $quote->quote_currency ?: 'USD' }})</th><td>{{ number_format((float) $quote->quoted_amount, 2) }}</td></tr>
                        </tbody></table>
                    @else
                        <p class="np-rq-muted">Our sales team is preparing your pricing. The approved quotation will be shown here once published.</p>
                    @endif
                </section>
                <x-storefront.account.bulk-quote.conversation :quote="$quote" :messages="$messages" />
                @if($canReply)
                    <section class="np-rq-card np-rq-message-form">
                        <h2>Send a message or request changes</h2>
                        <form method="POST" action="{{ route('account.bulk-quotes.message', $quote->reference) }}" enctype="multipart/form-data" data-single-submit>
                            @csrf
                            <label class="np-rq-label" for="np-rq-message">Your message</label>
                            <textarea id="np-rq-message" name="body" required minlength="2" maxlength="3000" placeholder="Type your message here...">{{ old('body') }}</textarea>
                            @error('body')<p class="np-rq-error" role="alert">{{ $message }}</p>@enderror
                            <div class="np-rq-message-actions"><label class="np-rq-upload">Attach files (Optional) <small>PDF, JPG, PNG or Excel · Max 10MB each</small><input type="file" name="files[]" accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" multiple></label><button type="submit" class="btn btn-red">Send Message</button></div>
                        </form>
                    </section>
                @endif
            </div>
            <div class="np-rq-column">
                <section id="np-rq-files" class="np-rq-card"><h2>Quotation Files</h2><p class="np-rq-card-subtitle">Download all files related to this quotation.</p><x-storefront.account.bulk-quote.files :quote="$quote" :files="$files" /></section>
                <section class="np-rq-card"><h2>Terms &amp; Notes</h2>
                    <div class="np-rq-term"><span class="np-rq-term-icon"><x-storefront.account.icon name="truck" :size="20" /></span><div><strong>Delivery Terms</strong><p>{{ $quote->needed_by?->format('M j, Y') ?: 'To be confirmed' }} · {{ $details['shipping_method'] ?? $quote->preferredShippingMethodLabel() }}</p></div></div>
                    <div class="np-rq-term"><span class="np-rq-term-icon"><x-storefront.account.icon name="payment" :size="20" /></span><div><strong>Payment Terms</strong><p>{{ $ready ? (($details['payment_terms'] ?? '') ?: 'Contact sales for payment terms') : 'Available when quotation is published' }}</p></div></div>
                    <div class="np-rq-term"><span class="np-rq-term-icon"><x-storefront.account.icon name="info" :size="20" /></span><div><strong>Additional Notes</strong><p>{{ $ready ? (($details['quotation_notes'] ?? '') ?: 'Please contact sales with any questions about this quotation.') : 'Awaiting quotation preparation' }}</p></div></div>
                </section>
                <section class="np-rq-card np-rq-next"><h2>Next Step</h2>
                    <p class="np-rq-card-subtitle">Please review the quotation and let us know how you would like to proceed.</p>
                    @if($quote->converted_order_id)
                        <p class="np-rq-success">Your quotation has been confirmed and an order created.</p>
                        @if($quote->convertedOrder && (int) $quote->convertedOrder->user_id === (int) auth()->id())
                            <a class="btn btn-red" href="{{ route('account.orders.show', $quote->convertedOrder) }}">View Your Order</a>
                        @endif
                    @elseif($confirmed)
                        <p class="np-rq-success">Quotation confirmed without an order.</p>
                        @if($priced)
                            <form action="{{ route('account.bulk-quotes.decision', $quote->reference) }}" method="POST" data-single-submit>@csrf
                                <button type="submit" name="decision" value="create_order" class="btn btn-red">Confirm &amp; Create Order</button>
                            </form>
                        @else
                            <p class="np-rq-muted">Order creation is available after your quotation includes the final price and quantity.</p>
                        @endif
                    @elseif($expired)
                        <p class="np-rq-warning">This quotation has expired. Please contact NextPlay Sales for an updated quotation.</p>
                    @elseif($quote->guest_decision === 'changes_requested')
                        <p class="np-rq-muted">Your revision request is under review. NextPlay will send an updated quotation.</p>
                    @elseif($canConfirm)
                        <form action="{{ route('account.bulk-quotes.decision', $quote->reference) }}" method="POST" data-single-submit class="np-rq-decisions">@csrf
                            @if($priced)
                                <button type="submit" name="decision" value="create_order" class="btn btn-red">Confirm &amp; Create Order<small>Accept the quotation and create an order.</small></button>
                            @endif
                            <button type="submit" name="decision" value="accept_only" class="btn btn-white">Confirm Quotation Only<small>Accept without creating an order yet.</small></button>
                        </form>
                        <a class="btn btn-white" href="{{ route('account.bulk-quotes.changes', $quote->reference) }}">Request Changes<small>Ask us to revise the quotation.</small></a>
                        @unless($priced)<p class="np-rq-muted">Online order creation requires structured pricing and quantity. You can still confirm the offer or request changes.</p>@endunless
                    @else
                        <p class="np-rq-muted">NextPlay is reviewing your request. You'll be able to confirm it once a quotation is published.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-storefront.account.page>
