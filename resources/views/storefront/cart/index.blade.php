<x-layouts.storefront :seo="$seo">
    {{-- NEXTPLAY_CART_PROTOTYPE --}}
    <section class="np-cart-page">
        <div class="site-container">
            <nav class="np-cart-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                <span>Shopping Cart</span>
            </nav>

            <div class="np-cart-heading-row">
                <h1 class="font-display">Shopping Cart</h1>
                <span class="np-cart-count {{ $cart['is_empty'] ? 'np-cart-count--empty' : '' }}" data-cart-page-quantity>
                    {{ $cart['quantity'] }} item{{ $cart['quantity'] === 1 ? '' : 's' }}
                </span>
            </div>

            <div
                class="np-cart-page-feedback {{ session('status') ? '' : 'hidden' }}"
                data-cart-page-feedback
            >
                {{ session('status') }}
            </div>

            @if ($errors->any())
                <div class="np-cart-page-feedback np-cart-page-feedback--error">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($cart['is_empty'])
                <div class="np-cart-empty" data-cart-empty-state>
                    <div class="np-cart-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 96 96" fill="none" role="img">
                            <path d="M27 35.5h42l4 43H23l4-43Z" stroke="currentColor" stroke-width="3.2" stroke-linejoin="round"/>
                            <path d="M37.5 40V29.5C37.5 20.94 42.2 16 48 16s10.5 4.94 10.5 13.5V40" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
                            <path d="M48 7v-5M27.5 14.5 24 10.7M68.5 14.5 72 10.7" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h2>Your cart is empty</h2>
                    <p>Find something you love and add it to your cart.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-red np-cart-empty-cta">
                        <span>Shop Products</span>
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h11M11 6l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            @else
                <div class="np-cart-layout">
                    <div class="np-cart-items" data-cart-items-list>
                        @foreach ($cart['items'] as $item)
                            <x-storefront.cart.item-card :item="$item" />
                        @endforeach
                    </div>

                    <x-storefront.cart.summary-card :cart="$cart" />
                </div>
            @endif
        </div>
    </section>

    @if (! $cart['is_preview'])
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const feedback = document.querySelector('[data-cart-page-feedback]');

                const showCartFeedback = (message, type = 'success') => {
                    if (!feedback) return;
                    feedback.textContent = message || '';
                    feedback.classList.toggle('hidden', !message);
                    feedback.classList.toggle('np-cart-page-feedback--error', type !== 'success');
                };

                const refreshSummary = (cart) => {
                    if (!cart) return;

                    document.querySelectorAll('[data-cart-summary-root]').forEach((root) => {
                        root.querySelectorAll('[data-cart-money]').forEach((node) => {
                            const key = node.getAttribute('data-cart-money');
                            const prefix = node.getAttribute('data-cart-money-prefix') || '';
                            const value = Number(cart[key] || 0);
                            node.textContent = `${prefix}${money.format(value)}`;
                        });

                        const quantityNode = root.querySelector('[data-cart-quantity]');
                        if (quantityNode) {
                            const quantity = Number(cart.quantity || 0);
                            quantityNode.textContent = `${quantity}`;
                        }

                        const pill = root.querySelector('[data-coupon-pill]');
                        const couponCode = root.querySelector('[data-coupon-code]');
                        if (pill) pill.classList.toggle('hidden', !cart.coupon_code);
                        if (couponCode) couponCode.textContent = cart.coupon_code || '';

                        const checkoutLink = root.querySelector('[data-checkout-link]');
                        if (checkoutLink) {
                            checkoutLink.classList.toggle('pointer-events-none', !cart.checkout_ready);
                            checkoutLink.classList.toggle('opacity-50', !cart.checkout_ready);
                        }
                    });

                    const pageQuantity = document.querySelector('[data-cart-page-quantity]');
                    if (pageQuantity) {
                        const quantity = Number(cart.quantity || 0);
                        pageQuantity.textContent = `${quantity} item${quantity === 1 ? '' : 's'}`;
                    }

                    document.querySelectorAll('.storefront-cart-count-badge').forEach((node) => {
                        const quantity = Number(cart.quantity || 0);
                        node.textContent = quantity > 99 ? '99+' : String(quantity);
                        node.classList.toggle('hidden', quantity <= 0);
                    });
                };

                const setCardBusy = (card, busy) => {
                    if (!card) return;
                    card.classList.toggle('is-updating', busy);
                    card.querySelectorAll('[data-cart-quantity-button]').forEach((button) => {
                        button.disabled = busy || button.hasAttribute('data-originally-disabled');
                    });
                };

                document.querySelectorAll('[data-cart-quantity-button]:disabled').forEach((button) => {
                    button.setAttribute('data-originally-disabled', '1');
                });

                document.addEventListener('submit', async (event) => {
                    const form = event.target.closest('[data-cart-quantity-form]');
                    if (!form) return;

                    event.preventDefault();

                    const card = form.closest('[data-cart-item-card]');
                    if (!card || card.classList.contains('is-updating')) return;

                    setCardBusy(card, true);
                    showCartFeedback('', 'success');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: new FormData(form),
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const message = payload.message
                                || Object.values(payload.errors || {})?.[0]?.[0]
                                || 'Unable to update the cart quantity.';
                            throw new Error(message);
                        }

                        refreshSummary(payload.cart);

                        if (payload.item_html) {
                            const wrapper = document.createElement('div');
                            wrapper.innerHTML = payload.item_html.trim();
                            const updatedCard = wrapper.firstElementChild;
                            if (updatedCard) {
                                card.replaceWith(updatedCard);
                                updatedCard.classList.add('cart-item-card-updated');
                                setTimeout(() => updatedCard.classList.remove('cart-item-card-updated'), 700);
                                updatedCard.querySelectorAll('[data-cart-quantity-button]:disabled').forEach((button) => {
                                    button.setAttribute('data-originally-disabled', '1');
                                });
                            }
                        }

                        showCartFeedback(payload.message || 'Cart quantity updated.', 'success');
                    } catch (error) {
                        showCartFeedback(error.message || 'Unable to update the cart quantity.', 'error');
                        setCardBusy(card, false);
                    }
                });
            });
        </script>
    @endif
</x-layouts.storefront>
