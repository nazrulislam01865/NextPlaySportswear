@props(['item'])

@php
    $product = (array) ($item['product'] ?? []);
    $customization = (array) ($item['customization'] ?? []);
    $quantity = max(1, (int) ($item['quantity'] ?? 1));
    $minimumQuantity = max(1, (int) ($item['quantity_min'] ?? 1));
    $maximumQuantity = max($minimumQuantity, (int) ($item['quantity_max'] ?? 999));
    $decreaseQuantity = max($minimumQuantity, $quantity - 1);
    $increaseQuantity = min($maximumQuantity, $quantity + 1);
    $displayLineTotal = (float) ($item['line_subtotal'] ?? 0) + (float) ($item['customization_total'] ?? 0);
    $unitDisplayPrice = $quantity > 0
        ? ($displayLineTotal / $quantity)
        : (float) ($item['unit_price'] ?? 0);
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
        <p class="np-cart-item-unit">From ${{ number_format($unitDisplayPrice, 2) }} each</p>
        <div class="np-cart-item-saved">
            <span class="np-cart-item-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none">
                    <circle cx="10" cy="10" r="8" fill="currentColor"/>
                    <path d="m6.4 10.1 2.2 2.2 5-5" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span>Customization details saved</span>
        </div>

        <form method="POST" action="{{ route('cart.items.destroy', $item['key']) }}" class="np-cart-remove-form" data-cart-remove-form>
            @csrf
            @method('DELETE')
            <button type="submit" class="np-cart-remove-button">Remove</button>
        </form>
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
        <span><span data-cart-item-money="unit_total">${{ number_format($unitDisplayPrice, 2) }}</span> each</span>
    </div>
</article>
