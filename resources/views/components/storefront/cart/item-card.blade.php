@props(['item'])

@php
    $product = $item['product'];
    $customization = $item['customization'];
    $quantity = max(1, (int) ($item['quantity'] ?? 1));
    $minimumQuantity = max(1, (int) ($item['quantity_min'] ?? 1));
    $maximumQuantity = max($minimumQuantity, (int) ($item['quantity_max'] ?? 999));
    $decreaseQuantity = max($minimumQuantity, $quantity - 1);
    $increaseQuantity = min($maximumQuantity, $quantity + 1);
    $fulfillment = (array) ($customization['fulfillment'] ?? []);
    $production = is_array($fulfillment['production'] ?? null) ? $fulfillment['production'] : null;
    $shipping = is_array($fulfillment['shipping'] ?? null) ? $fulfillment['shipping'] : null;
@endphp

<article class="cart-item-card overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-card" data-cart-item-card data-cart-item-key="{{ $item['key'] }}">
    <div class="cart-item-main grid gap-0 md:grid-cols-[150px_minmax(0,1fr)]">
        <a href="{{ $product['url'] }}" class="cart-item-image np-product-square-media group border-b border-slate-100 bg-white p-4 md:border-b-0 md:border-r">
            <img
                src="{{ $product['image'] }}"
                alt="{{ $product['alt'] ?? $product['title'] }}"
                class="np-product-square-image transition duration-300 group-hover:scale-105"
                loading="lazy"
            >
        </a>

        <div class="min-w-0 p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-brand-blue">{{ $product['sport'] }}</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-slate-600">SKU: {{ $product['sku'] }}</span>
                    </div>

                    <h2 class="mt-3 max-w-2xl text-lg font-black leading-snug text-brand-ink sm:text-xl">
                        <a href="{{ $product['url'] }}" class="hover:text-brand-red">{{ $product['title'] }}</a>
                    </h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $product['category'] }}</p>
                </div>

                <div class="cart-item-total shrink-0 rounded-2xl bg-slate-50 px-4 py-3 text-left sm:text-right">
                    <p class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Item total</p>
                    <p class="mt-1 text-2xl font-black leading-none text-brand-ink" data-cart-item-money="line_total">${{ number_format($item['line_total'], 2) }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500"><span data-cart-item-money="unit_total">${{ number_format($item['unit_price'] + $item['customization_unit_price'] + ($item['shipping_unit_price'] ?? 0), 2) }}</span> each</p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-black uppercase tracking-[.14em] text-brand-red">Customization</p>
                    <dl class="mt-3 grid gap-2 text-sm">
                        <div class="cart-info-row">
                            <dt>Design</dt>
                            <dd>{{ $customization['design_option'] }}</dd>
                        </div>
                        <div class="cart-info-row">
                            <dt>Sizes</dt>
                            <dd>{{ $customization['size_summary'] }}</dd>
                        </div>
                        @if((bool) data_get($customization, 'sample.requested', false))
                            <div class="cart-info-row">
                                <dt>Sample</dt>
                                <dd>Requested <span class="block text-xs font-bold text-brand-red">+${{ number_format((float) data_get($customization, 'sample.charge', 0), 2) }} / order</span></dd>
                            </div>
                        @endif
                        <div class="cart-info-row">
                            <dt>Artwork</dt>
                            <dd>
                                {{ $customization['artwork_status'] }}
                                @if(!empty($customization['artwork_files']))
                                    <ul class="mt-1 space-y-1 text-xs">
                                        @foreach($customization['artwork_files'] as $artworkFile)
                                            <li class="break-words">{{ $artworkFile['original_name'] ?? 'Artwork file' }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] font-black uppercase tracking-[.14em] text-brand-red">Production &amp; shipping</p>
                    <dl class="mt-3 grid gap-2 text-sm">
                        @if($production)
                            <div class="cart-info-row">
                                <dt>Production</dt>
                                <dd>
                                    {{ $production['label'] ?? 'Standard production' }}
                                    <span class="block text-xs font-bold {{ ($production['amount'] ?? 0) > 0 ? 'text-brand-red' : 'text-green-700' }}">{{ $production['display_amount'] ?? 'Included' }}</span>
                                    @if(($production['minimum_days'] ?? 0) > 0 || ($production['maximum_days'] ?? 0) > 0)
                                        <span class="block text-xs text-slate-500">{{ $production['minimum_days'] ?? 0 }}–{{ $production['maximum_days'] ?? $production['minimum_days'] ?? 0 }} business days</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if($shipping)
                            <div class="cart-info-row">
                                <dt>Shipping</dt>
                                <dd>
                                    {{ $shipping['label'] ?? 'Selected shipping' }}
                                    <span class="block text-xs text-slate-500">{{ $shipping['display_amount'] ?? 'Included' }}</span>
                                </dd>
                            </div>
                        @endif
                    </dl>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $customization['notes'] ?: 'No special notes added yet.' }}</p>
                    <span class="mt-3 inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-black text-green-700">Proof review before production</span>
                </div>
            </div>
        </div>
    </div>

    <div class="cart-item-actions flex flex-col gap-3 border-t border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div class="cart-quantity-block">
            <div class="cart-quantity-control">
                <p class="cart-quantity-label">Quantity</p>
                <div class="cart-quantity-stepper inline-flex items-center overflow-hidden border border-slate-300 bg-white shadow-sm">
                <form method="POST" action="{{ route('cart.items.update', $item['key']) }}" class="m-0" data-cart-quantity-form>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="quantity" value="{{ $decreaseQuantity }}">
                    <button
                        type="submit"
                        class="cart-quantity-stepper-button"
                        data-cart-quantity-button
                        aria-label="Decrease quantity for {{ $product['title'] }}"
                        @disabled($quantity <= $minimumQuantity)
                    >−</button>
                </form>

                <span class="cart-quantity-stepper-value" aria-live="polite" data-cart-item-quantity>{{ $quantity }}</span>

                <form method="POST" action="{{ route('cart.items.update', $item['key']) }}" class="m-0" data-cart-quantity-form>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="quantity" value="{{ $increaseQuantity }}">
                    <button
                        type="submit"
                        class="cart-quantity-stepper-button"
                        data-cart-quantity-button
                        aria-label="Increase quantity for {{ $product['title'] }}"
                        @disabled($quantity >= $maximumQuantity)
                    >+</button>
                </form>
                </div>
            </div>
            <p class="cart-quantity-help">Tier price updates automatically.</p>
        </div>

        <div class="grid gap-2 sm:flex sm:items-center">
            <a href="{{ route('products.show', ['slug' => $product['slug'], 'cart_item' => $item['key']]).'#configure-product' }}" class="btn btn-white w-full sm:w-auto">Edit Options</a>
            <form method="POST" action="{{ route('cart.items.destroy', $item['key']) }}" data-cart-remove-form>
                @csrf
                @method('DELETE')
                <button class="btn w-full border border-red-200 bg-red-50 text-brand-red hover:bg-red-100 sm:w-auto" type="submit">Remove</button>
            </form>
        </div>
    </div>
</article>
