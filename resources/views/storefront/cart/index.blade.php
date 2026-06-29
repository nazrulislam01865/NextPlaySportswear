<x-layouts.storefront :seo="$seo">
    <section class="border-b border-slate-100 bg-white py-4">
        <div class="site-container text-sm font-semibold text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-brand-red">Home</a>
            <span class="mx-2">/</span>
            <span class="text-brand-ink">Shopping Cart</span>
        </div>
    </section>

    <section class="bg-gradient-to-b from-slate-50 to-white py-8 sm:py-10">
        <div class="site-container">
            <div class="mb-7 grid gap-5 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Review custom order</p>
                    <h1 class="mt-2 font-display text-4xl font-bold uppercase leading-none tracking-tight text-brand-ink sm:text-5xl">Shopping Cart</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">Review product details, sizes, artwork notes, discounts, and shipping estimate before checkout.</p>
                </div>

                <div class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white text-center text-[11px] font-black uppercase tracking-wide text-slate-500 shadow-sm sm:grid-cols-3">
                    <div class="bg-brand-red px-4 py-3 text-white">1. Cart</div>
                    <div class="px-4 py-3">2. Checkout</div>
                    <div class="px-4 py-3">3. Proof</div>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($cart['is_preview'])
                <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
                    Preview mode is showing a sample custom order from the demo cart link. Add a product to create your own session cart.
                </div>
            @endif

            @if ($cart['is_empty'])
                <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <div class="rounded-[26px] border border-slate-200 bg-white p-6 text-center shadow-card sm:p-10">
                        <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-slate-50 text-3xl shadow-sm">🛒</div>
                        <h2 class="mt-5 font-display text-3xl font-bold uppercase leading-tight text-brand-ink sm:text-4xl">Your cart is empty</h2>
                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600">Browse custom jerseys, uniforms, caps, bags, and team apparel.</p>
                        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                            <a href="{{ route('products.index') }}" class="btn btn-red">Shop Products</a>
                            <a href="{{ route('cart.index', ['preview' => 1]) }}" class="btn btn-white">Preview Cart Design</a>
                        </div>
                    </div>

                    <x-storefront.cart.summary-card :cart="$cart" />
                </div>
            @else
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_380px]">
                    <div class="grid gap-4">
                        @foreach ($cart['items'] as $item)
                            <x-storefront.cart.item-card :item="$item" />
                        @endforeach

                        <x-storefront.cart.trust-panel :points="$cart['trust_points']" />
                    </div>

                    <x-storefront.cart.summary-card :cart="$cart" />
                </div>
            @endif
        </div>
    </section>

    <section class="bg-slate-50 py-10 sm:py-14">
        <div class="site-container">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Need more team gear?</p>
                    <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-brand-ink sm:text-4xl">Recommended Products</h2>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-white">View All Products</a>
            </div>

            <div class="grid-4">
                @foreach ($recommendedProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.storefront>
