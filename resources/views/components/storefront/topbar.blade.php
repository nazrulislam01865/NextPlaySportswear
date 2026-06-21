<div class="bg-brand-dark text-white">
    <div class="site-container flex flex-col items-center justify-between gap-2 py-2 text-center text-xs font-bold sm:flex-row sm:text-left">
        <p>
            Custom team uniforms, jerseys, hoodies, caps, bags, and sports gear. Bulk quotes available.
        </p>

        <div class="flex gap-4 whitespace-nowrap">
            <a href="{{ route('quote.request') }}" class="hover:underline">
                Request Bulk Quote
            </a>

            <a href="{{ route('orders.track') }}" class="hover:underline">
                Track Order
            </a>

            <a href="https://wa.me/{{ preg_replace('/\D+/', '', config('storefront.whatsapp')) }}" class="hover:underline">
                WhatsApp Us
            </a>
        </div>
    </div>
</div>
