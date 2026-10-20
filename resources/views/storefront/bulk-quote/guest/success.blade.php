<x-layouts.storefront :seo="['title' => 'Bulk Quote Request Submitted | NextPlay', 'robots' => 'noindex, nofollow']">
    <div class="np-gq-page np-gq-page--success"><div class="np-gq-container">
        <div class="np-gq-confirmation np-gq-panel">
            <div class="np-gq-success-heading"><span class="np-gq-success-check"><x-storefront.bulk-quote.guest.icon name="check" size="50" /></span><div><h1>Your Bulk Quote Request Has Been Submitted!</h1><p>Thanks for choosing NextPlay Sportswear. We've received your request and our team will prepare a custom quotation for you.</p></div></div>
            <div class="np-gq-summary-three">
                <div><small>Quote Request Number</small><strong>{{ $quote->reference }}</strong></div>
                <div><small>Submitted Date</small><strong>{{ \App\Support\ViewerTime::render($quote->created_at, 'M j, Y') }}</strong></div>
                <div class="np-gq-estimate"><small>Expected Response</small><strong><x-storefront.bulk-quote.guest.icon name="clock" size="27" /> Within 1–2 Business Days</strong><span>We'll send an email as soon as your quote is ready.</span></div>
            </div>
            <div class="np-gq-blue-panel np-gq-email-panel">
                <div class="np-gq-large-icon"><x-storefront.bulk-quote.guest.icon name="mail" size="38" /></div>
                <div class="np-gq-grow"><h2>We've sent a confirmation email</h2><p>A copy of your quote request and a secure link to track your quote have been sent to the email address you provided. Please check your inbox (and spam folder, just in case).</p>
                    @if($copyUrl)
                        <div class="np-gq-copy"><input aria-label="Secure quote tracking link" type="text" readonly value="{{ $copyUrl }}" id="np-quote-link"><button type="button" class="btn btn-white" data-quote-copy-link><x-storefront.bulk-quote.guest.icon name="copy" size="23" /> Copy Link</button></div>
                    @else
                        <p>Your secure link is in your confirmation email. Keep it private.</p>
                    @endif
                </div>
            </div>
            <x-storefront.bulk-quote.guest.panel title="Quote Status" subtitle="Here's where you are in the process:">
                <div class="np-gq-four-steps"><div class="active"><span><x-storefront.bulk-quote.guest.icon name="check" size="31" /></span><strong>1. Request Received</strong><small>{{ \App\Support\ViewerTime::render($quote->created_at, 'M j, Y') }}</small></div><div><span><x-storefront.bulk-quote.guest.icon name="file" size="30" /></span><strong>2. Under Review</strong><small>Our team is preparing your custom quote.</small></div><div><span><x-storefront.bulk-quote.guest.icon name="mail" size="30" /></span><strong>3. Quote Sent</strong><small>You'll receive an email with your quote and files.</small></div><div><span><x-storefront.bulk-quote.guest.icon name="download" size="30" /></span><strong>4. Review &amp; Proceed</strong><small>Check your quote, download files, and place your order when ready.</small></div></div>
            </x-storefront.bulk-quote.guest.panel>
            <div class="np-gq-blue-panel np-gq-access"><div class="np-gq-large-icon"><x-storefront.bulk-quote.guest.icon name="file" size="37" /></div><div><h2>Track Your Quote &amp; Access Files Anytime</h2><p>Use the link above to check your quote status, view your customized quote, and access related files such as design mockups and team details.</p></div><ul><li>Check real-time quote status</li><li>View and download your quote and files</li><li>Continue the conversation with our team</li></ul></div>
            <div class="np-gq-center-actions"><a class="btn btn-red" href="{{ route('quote.guest.show', $quote->reference) }}">Track Quote Status <x-storefront.bulk-quote.guest.icon name="arrow-right" size="21" /></a><a class="btn btn-white" href="{{ route('home') }}">Back to Home</a></div>
        </div>
    </div></div>
</x-layouts.storefront>
