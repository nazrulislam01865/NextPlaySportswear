@props(['item'])

@php
    $product = $item['product'];
    $customization = $item['customization'];
@endphp

<article class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-card">
    <div class="grid gap-0 lg:grid-cols-[190px_1fr]">
        <a href="{{ $product['url'] }}" class="group block h-full min-h-[190px] overflow-hidden bg-slate-100">
            <img
                src="{{ $product['image'] }}"
                alt="{{ $product['alt'] ?? $product['title'] }}"
                class="h-full min-h-[190px] w-full object-cover transition duration-300 group-hover:scale-105"
                loading="lazy"
            >
        </a>

        <div class="grid gap-5 p-5 lg:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-brand-blue">{{ $product['sport'] }}</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-slate-600">SKU: {{ $product['sku'] }}</span>
                    </div>

                    <h2 class="mt-3 text-xl font-black leading-tight text-brand-ink lg:text-2xl">
                        <a href="{{ $product['url'] }}" class="hover:text-brand-red">{{ $product['title'] }}</a>
                    </h2>
                    <p class="mt-2 text-sm font-semibold text-slate-500">{{ $product['category'] }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-left xl:text-right">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-slate-400">Item total</p>
                    <p class="mt-1 text-2xl font-black text-brand-ink">${{ number_format($item['line_total'], 2) }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">${{ number_format($item['unit_price'] + $item['customization_unit_price'], 2) }} each</p>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Customization</p>
                    <dl class="mt-3 grid gap-2 text-sm">
                        <div class="flex gap-2">
                            <dt class="w-24 shrink-0 font-black text-slate-700">Design</dt>
                            <dd class="text-slate-600">{{ $customization['design_option'] }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 shrink-0 font-black text-slate-700">Sizes</dt>
                            <dd class="text-slate-600">{{ $customization['size_summary'] }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="w-24 shrink-0 font-black text-slate-700">Artwork</dt>
                            <dd class="text-slate-600">{{ $customization['artwork_status'] }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Production notes</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $customization['notes'] ?: 'No special notes added yet.' }}</p>
                    <div class="mt-3 inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-black text-green-700">
                        Proof review before production
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <form method="POST" action="{{ route('cart.items.update', $item['key']) }}" class="flex items-center gap-3">
                    @csrf
                    @method('PATCH')
                    <label for="quantity-{{ $item['key'] }}" class="text-sm font-black text-slate-700">Qty</label>
                    <input
                        id="quantity-{{ $item['key'] }}"
                        type="number"
                        name="quantity"
                        value="{{ $item['quantity'] }}"
                        min="1"
                        max="999"
                        class="h-11 w-24 rounded-xl border border-slate-300 bg-white px-3 text-center text-sm font-black text-slate-800 outline-none focus:border-brand-blue"
                    >
                    <button class="btn btn-light" type="submit">Update</button>
                </form>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ $product['url'] }}#customize" class="btn btn-white">Edit Options</a>
                    <form method="POST" action="{{ route('cart.items.destroy', $item['key']) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn border border-red-200 bg-red-50 text-brand-red hover:bg-red-100" type="submit">Remove</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</article>
