@props(['proof', 'class' => '', 'admin' => false, 'compact' => false])
@php
    $url = $admin
        ? route('admin.orders.artwork-proofs.file', [$proof->order, $proof])
        : route('account.artwork.file', $proof);
@endphp
<div {{ $attributes->class(['np-artwork-preview', 'np-artwork-preview--compact' => $compact, $class]) }}>
    @if($proof->isImage())
        <img src="{{ $url }}" alt="Artwork proof version {{ $proof->version }} for {{ $proof->design_name }}" loading="{{ $compact ? 'lazy' : 'eager' }}">
    @else
        @if(!$compact)
            <object data="{{ $url }}" type="application/pdf" aria-label="Artwork proof PDF" class="np-artwork-pdf">
                <div class="np-artwork-file-placeholder"><x-storefront.account.icon name="artwork" :size="42" /><strong>Artwork proof (PDF)</strong><a href="{{ $url }}?download=1">Download to review</a></div>
            </object>
        @else
            <span class="np-artwork-file-placeholder"><x-storefront.account.icon name="artwork" :size="28" />PDF Proof</span>
        @endif
    @endif
</div>
