<footer class="bg-[#0c203c] py-12 text-slate-300">
    <div class="site-container">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-[1.5fr_repeat(4,1fr)]">
            <div>
                <a href="{{ route('home') }}" class="mb-4 flex items-center gap-3 font-display text-xl font-bold uppercase text-white">
                    <span class="grid h-8 w-8 place-items-center rounded-lg border-[3px] border-brand-red text-brand-red">
                        □
                    </span>

                    <span>
                        NextPlay <span class="text-brand-red">Sportswear</span>
                    </span>
                </a>

                <p class="text-sm leading-6">
                    Custom sportswear, apparel, accessories, and promotional products for teams, schools, businesses, and events.
                </p>
            </div>

            <div>
                <h4 class="mb-3 font-extrabold text-white">Shop</h4>
                <ul class="grid gap-2 text-sm">
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Team Uniforms</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Custom Jerseys</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Hoodies</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Caps</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Bags</a></li>
                </ul>
            </div>

            <div>
                <h4 class="mb-3 font-extrabold text-white">Sports</h4>
                <ul class="grid gap-2 text-sm">
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Football</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Baseball</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Basketball</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Soccer</a></li>
                    <li><a href="{{ route('products.index') }}" class="hover:text-white">Volleyball</a></li>
                </ul>
            </div>

            <div>
                <h4 class="mb-3 font-extrabold text-white">Support</h4>
                <ul class="grid gap-2 text-sm">
                    <li><a href="#process" class="hover:text-white">How to Order</a></li>
                    <li><a href="{{ route('quote.request') }}" class="hover:text-white">Bulk Quote</a></li>
                    <li><a href="{{ route('orders.track') }}" class="hover:text-white">Track Order</a></li>
                    <li><a href="#" class="hover:text-white">Size Guide</a></li>
                    <li><a href="#" class="hover:text-white">Shipping Information</a></li>
                    <li><a href="#" class="hover:text-white">Contact Us</a></li>
                </ul>
            </div>

            <div>
                <h4 class="mb-3 font-extrabold text-white">Contact</h4>
                <ul class="grid gap-2 text-sm">
                    <li>Email: {{ config('storefront.email') }}</li>
                    <li>WhatsApp: {{ config('storefront.whatsapp') }}</li>
                    <li>Business Hours: Mon–Fri</li>
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-col justify-between gap-3 border-t border-white/10 pt-5 text-xs text-slate-400 sm:flex-row">
            <span>© {{ date('Y') }} {{ config('storefront.name') }}. All rights reserved.</span>
            <span>Privacy Policy · Terms · Accessibility</span>
        </div>
    </div>
</footer>
