<x-storefront.account.page :seo="$seo" title="Request Artwork Changes" subtitle="" :account="$account" :navigation="$navigation" content-only>
    <div class="np-artwork">
        <x-storefront.account.artwork.breadcrumb :order="$proof" end="Request Changes" />
        <header class="np-artwork-heading"><h1>Request Artwork Changes</h1><p>Tell our design team exactly what needs to be updated.</p><p class="np-artwork-muted">The more details you provide, the faster we can make the right changes to your design.</p></header>
        <section class="np-artwork-panel np-artwork-changes-grid">
            <div class="np-artwork-changes-preview">
                <div class="np-artwork-changes-proof-heading"><h2>Current Proof v{{ $proof->version }}</h2><x-storefront.account.artwork.status :status="$proof->status" /></div>
                <p><strong>Order #{{ $proof->order->order_number }}</strong> <span>·</span> {{ $proof->orderItem->product_name }}</p>
                <p class="np-artwork-muted">Uploaded {{ $proof->created_at?->format('M j, Y') }}</p>
                <x-storefront.account.artwork.preview :proof="$proof" class="np-artwork-changes-image" />
                <div class="np-artwork-thumbnails"><a class="is-active" href="{{ route('account.artwork.file', $proof) }}" target="_blank" rel="noopener"><x-storefront.account.artwork.preview :proof="$proof" compact /></a><a href="{{ route('account.artwork.file', $proof) }}" target="_blank" rel="noopener" aria-label="Zoom into full-resolution proof"><x-storefront.account.artwork.preview :proof="$proof" compact /></a></div>
            </div>
            <div class="np-artwork-change-form">
                <h2>Revision Details</h2><p class="np-artwork-muted">Let us know what changes you'd like, and our design team will create an updated proof for your review.</p>
                <form method="POST" action="{{ route('account.artwork.changes.store', $proof) }}" enctype="multipart/form-data">@csrf
                    <label class="np-artwork-field-label" for="artwork-change-feedback">What would you like changed? <b>*</b></label>
                    <textarea id="artwork-change-feedback" name="feedback" minlength="10" maxlength="500" required rows="6" placeholder="Please describe what needs to change, including colors, sizes, names, or logo placement...">{{ old('feedback') }}</textarea>
                    <small class="np-artwork-count" id="artwork-feedback-count">Maximum 500 characters</small>
                    <fieldset class="np-artwork-checkboxes"><legend>Select the types of changes <span>(optional)</span></legend>
                        @foreach(['color'=>'Color changes','logo'=>'Logo placement','text'=>'Text / numbering','layout'=>'Layout adjustments','other'=>'Other'] as $key => $label)
                            <label><input type="checkbox" name="change_types[]" value="{{ $key }}" @checked(in_array($key, old('change_types', []), true))><span>{{ $label }}</span></label>
                        @endforeach
                    </fieldset>
                    <div class="np-artwork-upload-field"><label class="np-artwork-field-label" for="artwork-reference">Attach reference image or annotated file <span>(optional)</span></label>
                        <label class="np-artwork-dropzone" for="artwork-reference"><x-storefront.account.icon name="downloads" :size="29"/><strong>Choose a reference file</strong><span>PNG, JPG, WebP or PDF · up to 10 MB</span><input id="artwork-reference" type="file" name="reference_file" accept="image/png,image/jpeg,image/webp,application/pdf"></label>
                    </div>
                    <div class="np-artwork-priority-row"><fieldset><legend class="np-artwork-field-label">Priority</legend><div class="np-artwork-priority-options"><label><input type="radio" name="priority" value="normal" @checked(old('priority', 'normal') === 'normal')><span><strong>Normal</strong><small>Standard turnaround</small></span></label><label><input type="radio" name="priority" value="urgent" @checked(old('priority') === 'urgent')><span><strong>Urgent</strong><small>Faster turnaround</small></span></label></div></fieldset>
                        <div class="np-artwork-remaining"><strong>Allowed revisions remaining <x-storefront.account.icon name="info" :size="15" /></strong><div><b>{{ $remaining === null ? '∞' : max(0, $remaining - 1) }}</b><span>{{ $remaining === null ? 'Unlimited included revisions' : 'revisions remaining after this request' }}</span></div></div>
                    </div>
                    <div class="np-artwork-turnaround"><x-storefront.account.icon name="clock" :size="21" /><span><strong>Typical update time: 1–2 business days.</strong><small>We'll notify you by email once the updated proof is ready for your review.</small></span></div>
                    <div class="np-artwork-form-actions"><a class="np-artwork-button" href="{{ route('account.artwork.show', $proof) }}"><x-storefront.account.icon name="arrow-left" :size="19" /> Back to Proof</a><button type="submit" class="np-artwork-button np-artwork-button--primary" @disabled($remaining === 0)>Submit Revision Request <x-storefront.account.icon name="arrow-right" :size="19" /></button></div>
                    @if($remaining === 0)<p class="np-artwork-form-warning">The included revision limit has been reached. Please contact support for further changes.</p>@endif
                </form>
            </div>
        </section>
    </div>
</x-storefront.account.page>
