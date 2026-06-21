<x-layouts.storefront :seo="$seo">
    <section class="bg-slate-50 py-6">
        <div class="site-container text-sm font-semibold text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-brand-red">Home</a>
            <span class="mx-2">/</span>
            <span class="text-brand-ink">Shopping Cart</span>
        </div>
    </section>

    <section class="section-padding bg-white pt-10">
        <div class="site-container">
            <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Review your custom order</p>
                    <h1 class="mt-2 font-display text-5xl font-bold uppercase tracking-tight text-brand-ink lg:text-6xl">Shopping Cart</h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">Confirm product details, custom artwork notes, sizes, discounts, shipping estimate, and production support before checkout.</p>
                </div>

                <div class="grid grid-cols-3 overflow-hidden rounded-2xl border border-slate-200 bg-white text-center text-xs font-black uppercase tracking-wide text-slate-500 shadow-sm sm:min-w-[420px]">
                    <div class="bg-brand-red px-4 py-3 text-white">1. Cart</div>
                    <div class="px-4 py-3">2. Checkout</div>
                    <div class="px-4 py-3">3. Proof</div>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($cart['is_preview'])
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
                    Preview mode is showing a sample custom order from the demo cart link. Add a product to create your own session cart.
                </div>
            @endif

            @if ($cart['is_empty'])
                <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
                    <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-8 text-center shadow-card lg:p-12">
                        <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-white text-4xl shadow-sm">🛒</div>
                        <h2 class="mt-6 font-display text-4xl font-bold uppercase text-brand-ink">Your cart is empty</h2>
                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600">Browse custom jerseys, uniforms, caps, bags, and team apparel. You can also preview the designed cart layout with sample items.</p>
                        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                            <a href="{{ route('products.index') }}" class="btn btn-red">Shop Products</a>
                            <a href="{{ route('cart.index', ['preview' => 1]) }}" class="btn btn-white">Preview Cart Design</a>
                        </div>
                    </div>

                    <x-storefront.cart.summary-card :cart="$cart" />
                </div>
            @else
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
                    <div class="grid gap-5">
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

    <section class="section-padding bg-slate-50">
        <div class="site-container">
            <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Need more team gear?</p>
                    <h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">Recommended Products</h2>
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
