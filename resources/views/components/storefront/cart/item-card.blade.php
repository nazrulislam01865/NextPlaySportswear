@props(['item'])

@php
    $product = $item['product'];
    $customization = $item['customization'];
@endphp

<article class="cart-item-card overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-card">
    <div class="cart-item-main grid gap-0 md:grid-cols-[150px_minmax(0,1fr)]">
        <a href="{{ $product['url'] }}" class="cart-item-image group flex h-44 items-center justify-center border-b border-slate-100 bg-slate-50 p-4 md:h-auto md:min-h-[210px] md:border-b-0 md:border-r">
            <img
                src="{{ $product['image'] }}"
                alt="{{ $product['alt'] ?? $product['title'] }}"
                class="max-h-36 w-full object-contain transition duration-300 group-hover:scale-105 md:max-h-44"
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
                    <p class="mt-1 text-2xl font-black leading-none text-brand-ink">${{ number_format($item['line_total'], 2) }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">${{ number_format($item['unit_price'] + $item['customization_unit_price'], 2) }} each</p>
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
                    <p class="text-[11px] font-black uppercase tracking-[.14em] text-brand-red">Production notes</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $customization['notes'] ?: 'No special notes added yet.' }}</p>
                    <span class="mt-3 inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-black text-green-700">Proof review before production</span>
                </div>
            </div>
        </div>
    </div>

    <div class="cart-item-actions flex flex-col gap-3 border-t border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <form method="POST" action="{{ route('cart.items.update', $item['key']) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
            @csrf
            @method('PATCH')
            <div>
                <label for="quantity-{{ $item['key'] }}" class="text-xs font-black uppercase tracking-wide text-slate-600">Quantity</label>
                <input
                    id="quantity-{{ $item['key'] }}"
                    type="number"
                    name="quantity"
                    value="{{ $item['quantity'] }}"
                    min="1"
                    max="999"
                    class="mt-1 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-center text-sm font-black text-slate-800 outline-none focus:border-brand-blue sm:w-24"
                >
            </div>
            <button class="btn btn-light w-full sm:w-auto" type="submit">Update</button>
        </form>

        <div class="grid gap-2 sm:flex sm:items-center">
            <a href="{{ $product['url'] }}#customize" class="btn btn-white w-full sm:w-auto">Edit Options</a>
            <form method="POST" action="{{ route('cart.items.destroy', $item['key']) }}">
                @csrf
                @method('DELETE')
                <button class="btn w-full border border-red-200 bg-red-50 text-brand-red hover:bg-red-100 sm:w-auto" type="submit">Remove</button>
            </form>
        </div>
    </div>
</article>
