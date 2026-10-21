<x-layouts.storefront :seo="['title' => 'Review Quotation | NextPlay', 'robots' => 'noindex, nofollow']">
    @php
        $details = (array) $quote->quotation_details;
        $priced = !($details['pricing_pending'] ?? false) && (int) ($details['quantity'] ?? 0) > 0 && (float) $quote->quoted_amount > 0;
        $accepted = in_array($quote->status, [\App\Models\BulkQuoteRequest::STATUS_ACCEPTED, \App\Models\BulkQuoteRequest::STATUS_CONVERTED], true);
        $finalized = $accepted || in_array($quote->status, [\App\Models\BulkQuoteRequest::STATUS_CLOSED, \App\Models\BulkQuoteRequest::STATUS_REJECTED], true);
        $expired = $quote->quotation_valid_until && $quote->quotation_valid_until->lt(today());
        $canConfirm = !$finalized && !$expired && $quote->guest_decision !== 'changes_requested';
        $customer = auth('web')->user();
        $needsAccount = !$customer?->isCustomer();
        $accountMismatch = $customer?->isCustomer() && !hash_equals(strtolower(trim($quote->email)), strtolower(trim($customer->email)));
        $canLinkLegacyOrder = $quote->converted_order_id && !$quote->user_id;
        $continueUrl = route('quote.guest.review', $quote->reference);
    @endphp
    <div class="np-gq-page np-gq-page--review"><div class="np-gq-container">
        <a class="np-gq-back" href="{{ route('quote.guest.show', $quote->reference) }}"><x-storefront.bulk-quote.guest.icon name="arrow-left" size="17" /> Back to Quote Status</a>
        <div class="np-gq-review-head">
            <div><h1>Review Quotation</h1><p>Please review the quotation details, download the files, and let us know how you would like to proceed.</p></div>
            <div class="np-gq-review-reference"><div class="np-gq-review-pill"><x-storefront.bulk-quote.guest.icon name="file" size="24" /><strong>Quote #: {{ $quote->reference }}</strong><span>{{ $accepted ? 'Confirmed' : ($quote->guest_decision === 'changes_requested' ? 'Revision Requested' : 'Pending Review') }}</span></div><small>Sent on {{ \App\Support\ViewerTime::render($quote->quotation_sent_at, 'M j, Y') }} · Version {{ $quote->quotation_version }}</small></div>
        </div>
        @if(session('status'))<div class="np-gq-alert" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="np-gq-alert np-gq-alert--error" role="alert">{{ $errors->first() }}</div>@endif
        <div class="np-gq-two-col np-gq-review-top">
            <x-storefront.bulk-quote.guest.panel title="Quotation Summary" icon="file">
                <dl class="np-gq-facts"><dt>Quote Number</dt><dd>{{ $quote->reference }}</dd><dt>Submitted Date</dt><dd>{{ \App\Support\ViewerTime::render($quote->created_at, 'M j, Y') }}</dd><dt>Team / Organization</dt><dd>{{ $quote->organization ?: '—' }}</dd><dt>Products</dt><dd>{{ $quote->product_type }}</dd><dt>Quantity</dt><dd>{{ $priced ? number_format((int) ($details['quantity'] ?? 0)).' pieces' : 'See attached quotation' }}</dd><dt>Required Delivery</dt><dd>{{ $quote->needed_by?->format('M j, Y') ?: 'To be confirmed' }}</dd><dt>Status</dt><dd class="np-gq-good">{{ $accepted ? 'Confirmed' : ($quote->guest_decision === 'changes_requested' ? 'Changes Requested' : 'Pending Review') }}</dd><dt>Valid Until</dt><dd>{{ $quote->quotation_valid_until?->format('M j, Y') }}</dd><dt>Special Notes</dt><dd>{{ $quote->additional_notes ?: ($quote->artwork_details ?: '—') }}</dd></dl>
            </x-storefront.bulk-quote.guest.panel>
            <x-storefront.bulk-quote.guest.panel title="Quotation Files" subtitle="Download and review all files related to this quotation." icon="file">
                <x-storefront.bulk-quote.guest.files :quote="$quote" :files="$files" compact />
            </x-storefront.bulk-quote.guest.panel>
        </div>
        <div class="np-gq-three-col">
            <x-storefront.bulk-quote.guest.panel title="Pricing Summary" tone="blue" icon="calculator">
                @if(!$priced)
                    <p class="np-gq-muted">The attached formal quotation contains the offer details. Structured price and quantity are pending, so online order creation is unavailable until they are provided.</p>
                @else
                <dl class="np-gq-money"><dt>Subtotal ({{ number_format((int) ($details['quantity'] ?? 0)) }} pieces)</dt><dd>USD {{ number_format((float) ($details['subtotal'] ?? 0), 2) }}</dd><dt>Customization Fees</dt><dd>USD {{ number_format((float) ($details['customization'] ?? 0), 2) }}</dd><dt>Shipping (Estimated)</dt><dd>USD {{ number_format((float) ($details['shipping'] ?? 0), 2) }}</dd><dt>Total (USD)</dt><dd>USD {{ number_format((float) $quote->quoted_amount, 2) }}</dd></dl>
                @endif
            </x-storefront.bulk-quote.guest.panel>
            <x-storefront.bulk-quote.guest.panel title="Delivery & Payment Terms" tone="blue" icon="truck">
                <dl class="np-gq-facts"><dt>Estimated Delivery</dt><dd>{{ $quote->needed_by?->format('M j, Y') ?: 'To be confirmed' }}</dd><dt>Shipping Method</dt><dd>{{ ($details['shipping_method'] ?? null) ?: $quote->preferredShippingMethodLabel() }}</dd><dt>Payment Terms</dt><dd>{{ ($details['payment_terms'] ?? null) ?: 'Contact sales' }}</dd><dt>Currency</dt><dd>{{ $quote->quote_currency ?: 'USD' }} (US Dollars)</dd></dl>
            </x-storefront.bulk-quote.guest.panel>
            <x-storefront.bulk-quote.guest.panel title="Quotation Notes" tone="warm" icon="file"><p class="np-gq-note">{{ ($details['quotation_notes'] ?? null) ?: 'Pricing and delivery depend on final design approval and payment confirmation.' }}</p></x-storefront.bulk-quote.guest.panel>
        </div>
        <div class="np-gq-review-bottom">
            <x-storefront.bulk-quote.guest.panel title="Send a message or request changes" subtitle="If you have any questions or would like to request changes, please let us know below." icon="message">
                @unless($finalized)
                    <form method="POST" action="{{ route('quote.guest.message', $quote->reference) }}" enctype="multipart/form-data" data-single-submit>
                        @csrf
                        <label class="np-gq-sr" for="quote-message-body">Your message</label>
                        <textarea id="quote-message-body" name="body" rows="3" placeholder="Type your message or request here..." required maxlength="3000">{{ old('body') }}</textarea>
                        <div class="np-gq-form-actions"><label class="np-gq-upload"><x-storefront.bulk-quote.guest.icon name="upload" size="25" /><span>Attach files (Optional)<small>PDF, JPG, PNG, or Excel (Max 10MB each)</small></span><input type="file" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.xls,.xlsx"></label><button class="btn btn-red" type="submit">Send Message</button></div>
                    </form>
                @else
                    <p class="np-gq-muted">This quotation has already been finalized. Contact sales if you need help.</p>
                @endunless
            </x-storefront.bulk-quote.guest.panel>
            <x-storefront.bulk-quote.guest.panel title="Next Step" subtitle="Choose how you would like to proceed with this quotation." tone="warm" icon="arrow-right">
                @if($accepted)
                    <div class="np-gq-alert" role="status"><strong>Quotation confirmed.</strong><p>{{ $quote->converted_order_id ? 'Order '.$quote->convertedOrder?->order_number.' has been created. Our team will contact you with invoice details.' : 'You accepted the quotation without creating an order.' }}</p></div>
                    @if($quote->converted_order_id && $quote->user_id && (int) $customer?->id === (int) $quote->user_id)
                        <a class="btn btn-white np-gq-wide" href="{{ route('account.orders.show', $quote->convertedOrder) }}">View Order in My Account</a>
                    @endif
                    @if((!$quote->converted_order_id && $priced) || $canLinkLegacyOrder)
                        @if($needsAccount)
                            <p class="np-gq-account-hint">{{ $canLinkLegacyOrder ? 'Create an account or sign in to link your existing order.' : 'Create an account or sign in before creating an order.' }} Use the same email as your quotation.</p>
                            <div class="np-gq-account-actions"><a class="btn btn-red np-gq-wide" href="{{ route('register', ['redirect' => $continueUrl]) }}"><x-storefront.bulk-quote.guest.icon name="cart" size="18" /> Create Account to Order</a><a class="btn btn-white np-gq-wide" href="{{ route('login', ['redirect' => $continueUrl]) }}">Already registered? Sign in</a></div>
                        @elseif($accountMismatch)
                            <p class="np-gq-alert np-gq-alert--error">Your signed-in email does not match the quote email. Sign in with the account matching this quotation before placing an order.</p>
                        @else
                            <form method="POST" action="{{ route('quote.guest.decision', $quote->reference) }}" class="np-gq-decision" data-single-submit>
                                @csrf
                                <button class="btn btn-red np-gq-wide" name="decision" value="create_order" type="submit"><x-storefront.bulk-quote.guest.icon name="cart" size="18" />{{ $canLinkLegacyOrder ? 'Link Order to Account' : 'Confirm & Create Order' }}<small>{{ $canLinkLegacyOrder ? 'Connect your existing order to this account.' : 'Create an order from your accepted quotation.' }}</small></button>
                            </form>
                        @endif
                    @endif
                @elseif($expired)
                    <p class="np-gq-muted">This quotation has expired. Please contact sales for an updated quotation.</p>
                @elseif($quote->guest_decision === 'changes_requested')
                    <p class="np-gq-muted">Your requested changes are with our team. We will send an updated quotation for your approval.</p>
                @elseif($canConfirm)
                    <form method="POST" action="{{ route('quote.guest.decision', $quote->reference) }}" class="np-gq-decision" data-single-submit>
                        @csrf
                        <button class="btn btn-white np-gq-wide" name="decision" value="accept_only" type="submit"><x-storefront.bulk-quote.guest.icon name="file" size="21" />Confirm Quotation Only<small>Accept the quotation without creating an order yet.</small></button>
                        @unless($needsAccount || $accountMismatch || !$priced)
                            <button class="btn btn-red np-gq-wide" name="decision" value="create_order" type="submit"><x-storefront.bulk-quote.guest.icon name="cart" size="18" />Confirm &amp; Create Order<small>Accept this quotation and create an order.</small></button>
                        @endunless
                    </form>
                    @if($needsAccount && $priced)
                        <p class="np-gq-account-hint">To create an order, first register or sign in with your quotation email.</p>
                        <div class="np-gq-account-actions"><a class="btn btn-red np-gq-wide" href="{{ route('register', ['redirect' => $continueUrl]) }}">Create Account to Order</a><a class="btn btn-white np-gq-wide" href="{{ route('login', ['redirect' => $continueUrl]) }}">Already registered? Sign in</a></div>
                    @elseif($accountMismatch)
                        <p class="np-gq-alert np-gq-alert--error">Please sign in with the email on this quotation to place an order.</p>
                    @endif
                    <a class="btn btn-white np-gq-wide" href="{{ route('quote.guest.changes', $quote->reference) }}"><x-storefront.bulk-quote.guest.icon name="edit" size="21" />Request Changes<small>Let us know if you need any changes to this quotation.</small></a>
                    @unless($priced)<p class="np-gq-muted">You can confirm or request changes now. Online order creation requires the final structured price and quantity.</p>@endunless
                @else
                    <p class="np-gq-muted">This quotation is not available for confirmation. Please contact sales.</p>
                @endif
            </x-storefront.bulk-quote.guest.panel>
        </div>
        <x-storefront.bulk-quote.guest.messages :messages="$messages" :quote="$quote" />
    </div></div>
</x-layouts.storefront>
