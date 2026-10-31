@props(['item'])

@php
    $product = (array) ($item['product'] ?? []);
    $customization = (array) ($item['customization'] ?? []);
    $quantity = max(1, (int) ($item['quantity'] ?? 1));
    $minimumQuantity = max(1, (int) ($item['quantity_min'] ?? 1));
    $maximumQuantity = max($minimumQuantity, (int) ($item['quantity_max'] ?? 999));
    $decreaseQuantity = max($minimumQuantity, $quantity - 1);
    $increaseQuantity = min($maximumQuantity, $quantity + 1);
    // Use the same configured total that was calculated on the product page.
    // This includes selected options/production/size charges and product-level
    // shipping, so the cart cannot fall back to showing only the tier base price.
    $displayLineTotal = (float) ($item['line_total'] ?? 0);
    if ($displayLineTotal <= 0) {
        $displayLineTotal = (float) ($item['line_subtotal'] ?? 0)
            + (float) ($item['customization_total'] ?? 0)
            + (float) ($item['product_shipping_total'] ?? 0);
    }
    // Product unit price must not fold a once-per-order shipping fee into
    // each unit; doing so misrepresents the sale and price-table unit rate.
    $unitDisplayPrice = (float) ($item['unit_price'] ?? 0);
    $customizationTotal = (float) ($item['customization_total'] ?? 0);
    $shippingTotal = (float) ($item['product_shipping_total'] ?? 0);
@endphp

<article class="np-cart-item cart-item-card" data-cart-item-card data-cart-item-key="{{ $item['key'] }}">
    <a href="{{ $product['url'] }}" class="np-cart-item-media" aria-label="View {{ $product['title'] }}">
        <img
            src="{{ $product['image'] }}"
            alt="{{ $product['alt'] ?? $product['title'] }}"
            loading="lazy"
            width="420"
            height="420"
        >
    </a>

    <div class="np-cart-item-details">
        <h2><a href="{{ $product['url'] }}">{{ $product['title'] }}</a></h2>
        <p class="np-cart-item-unit">${{ number_format($unitDisplayPrice, 2) }} per piece @if($customizationTotal > 0) · plus customisation @endif @if($shippingTotal > 0) · shipping shown in item total @endif</p>
        @if ((float) ($item['offer_savings'] ?? 0) > 0)
            <p class="text-xs font-bold text-green-700">Sale applied: save ${{ number_format((float) $item['offer_savings'], 2) }} on the product price
                <span class="text-slate-500">(${{ number_format((float) $item['original_unit_price'], 2) }} before offer / ${{ number_format((float) $item['unit_price'], 2) }} after offer)</span>
            </p>
        @endif
        @if(filled($item['configuration_error'] ?? null))
            <div role="alert" class="mt-2 border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                <p>{{ $item['configuration_error'] }}</p>
                <a class="mt-1 inline-block font-bold underline" href="{{ route('products.show', ['slug' => $item['product_slug'], 'cart_item' => $item['key']]) }}#configure-product">Update product options</a>
            </div>
        @endif
        <div class="np-cart-item-saved">
            <span class="np-cart-item-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none">
                    <circle cx="10" cy="10" r="8" fill="currentColor"/>
                    <path d="m6.4 10.1 2.2 2.2 5-5" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span>Customization details saved</span>
        </div>

        <div class="np-cart-remove-form flex items-center gap-4">
            <a
                href="{{ route('products.show', ['slug' => $item['product_slug'], 'cart_item' => $item['key']]) }}#configure-product"
                class="np-cart-remove-button font-bold"
                aria-label="Edit {{ $product['title'] }} in your cart"
            >Edit</a>
            <form method="POST" action="{{ route('cart.items.destroy', $item['key']) }}" data-cart-remove-form>
                @csrf
                @method('DELETE')
                <button type="submit" class="np-cart-remove-button">Remove</button>
            </form>
        </div>
    </div>

    <div class="np-cart-item-quantity">
        <p>Quantity</p>
        <div class="np-cart-quantity-stepper cart-quantity-stepper">
            <form method="POST" action="{{ route('cart.items.update', $item['key']) }}" data-cart-quantity-form>
                @csrf
                @method('PATCH')
                <input type="hidden" name="quantity" value="{{ $decreaseQuantity }}">
                <button
                    type="submit"
                    class="np-cart-quantity-button cart-quantity-stepper-button"
                    data-cart-quantity-button
                    aria-label="Decrease quantity for {{ $product['title'] }}"
                    @disabled($quantity <= $minimumQuantity)
                >−</button>
            </form>

            <span class="np-cart-quantity-value cart-quantity-stepper-value" aria-live="polite" data-cart-item-quantity>{{ $quantity }}</span>

            <form method="POST" action="{{ route('cart.items.update', $item['key']) }}" data-cart-quantity-form>
                @csrf
                @method('PATCH')
                <input type="hidden" name="quantity" value="{{ $increaseQuantity }}">
                <button
                    type="submit"
                    class="np-cart-quantity-button cart-quantity-stepper-button"
                    data-cart-quantity-button
                    aria-label="Increase quantity for {{ $product['title'] }}"
                    @disabled($quantity >= $maximumQuantity)
                >+</button>
            </form>
        </div>
    </div>

    <div class="np-cart-item-total">
        <p>Item total</p>
        <strong data-cart-item-money="line_total">${{ number_format($displayLineTotal, 2) }}</strong>
        <span>Includes ${{ number_format($shippingTotal, 2) }} shipping</span>
    </div>
</article>
