@props(['product', 'isEditing' => false, 'mobileReview' => false])

<div class="np-custom-order-actions{{ $mobileReview ? ' np-product-review-mobile-actions' : '' }}">
        <button
            type="submit"
            form="np-product-config-form"
            class="btn btn-secondary btn-xl np-custom-order-add-to-cart"
            title="Check required selections and add this product to cart"
            aria-label="{{ $isEditing ? 'Update cart item' : 'Add to cart' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l3-7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="18" cy="19" r="1.5"/></svg>
            {{ $isEditing ? 'Update Cart Item' : 'Add to Cart' }}
        </button>
        <a class="btn btn-outline btn-xl np-custom-order-save-quote" href="{{ route('quote.request', ['product' => $product['slug']]) }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>
            Save as Quote
        </a>
        <button type="button" class="btn btn-outline btn-xl np-custom-order-save-later" @click="toggleWishlist()" :aria-pressed="wishlisted ? 'true' : 'false'" :disabled="wishlistBusy">
            <span aria-hidden="true">♡</span><span x-text="wishlisted ? 'Saved for Later' : 'Save for Later'"></span>
        </button>
        <p class="np-custom-order-disabled-note" x-show="validationIssue() !== null" x-cloak x-text="validationIssue()?.message || ''"></p>
</div>
