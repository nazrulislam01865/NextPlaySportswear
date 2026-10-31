@php
    $siteName = config('storefront.name', 'NextPlay Sportswear');
    $title = $title ?? 'Terms and Conditions';
    $subtitle = $subtitle ?? 'Draft for review';
    $description = $description ?? 'These terms explain the ordering process and your responsibilities when shopping with NextPlay Sportswear.';
@endphp

<x-layouts.storefront :seo="[
    'title' => $title . ' | ' . $siteName,
    'description' => $description,
]">
    <div class="bg-white py-8 sm:py-12 lg:py-14">
        <div class="site-container">
            <div class="max-w-3xl">
                {{-- Breadcrumb --}}
                <nav class="nps-breadcrumb mb-6 sm:mb-8" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><span aria-current="page">Terms and Conditions</span></li>
                    </ol>
                </nav>

                {{-- Page Header --}}
                <header class="mb-8 sm:mb-10">
                    <h1 class="text-3xl font-bold tracking-tight text-[#051e44] sm:text-4xl lg:text-[40px] leading-tight">
                        Terms and Conditions
                    </h1>
                    <p class="mt-2 text-sm text-[#52637a]">
                        {{ $subtitle }}
                    </p>
                    <p class="mt-5 text-base sm:text-lg leading-relaxed text-[#26364b]">
                        These terms explain the ordering process and your responsibilities when shopping with NextPlay Sportswear.
                    </p>
                </header>

                {{-- Terms Sections --}}
                <div class="space-y-8 sm:space-y-10 text-base leading-relaxed text-[#26364b]">
                    {{-- 1. About these terms --}}
                    <section aria-labelledby="section-about-terms" class="space-y-3">
                        <h2 id="section-about-terms" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            1. About these terms
                        </h2>
                        <p>
                            These terms apply when you browse our website, request a quotation, or place an order. Please read them before ordering.
                        </p>
                    </section>

                    {{-- 2. Product information and pricing --}}
                    <section aria-labelledby="section-product-pricing" class="space-y-3">
                        <h2 id="section-product-pricing" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            2. Product information and pricing
                        </h2>
                        <p>
                            Product details, prices, currencies, and available options are shown on the website or in your quotation. Check your selected specifications and order total before payment.
                        </p>
                    </section>

                    {{-- 3. Orders and payment --}}
                    <section aria-labelledby="section-orders-payment" class="space-y-3">
                        <h2 id="section-orders-payment" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            3. Orders and payment
                        </h2>
                        <p>
                            Review your contact details, addresses, quantities, and customisation instructions before placing an order. Payment details and the amount due are shown at checkout or in your quotation.
                        </p>
                    </section>

                    {{-- 4. Artwork and customisation --}}
                    <section aria-labelledby="section-artwork-customisation" class="space-y-3">
                        <h2 id="section-artwork-customisation" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            4. Artwork and customisation
                        </h2>
                        <p>
                            We will send artwork for your approval before production begins. Check all names, numbers, logos, colours, sizes, and placement carefully. Production starts after artwork approval.
                        </p>
                    </section>

                    {{-- 5. Production and delivery --}}
                    <section aria-labelledby="section-production-delivery" class="space-y-3">
                        <h2 id="section-production-delivery" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            5. Production and delivery
                        </h2>
                        <p>
                            Production and delivery estimates are provided with your order. Timing depends on artwork approval, product requirements, and the selected shipping method. Any remote-area charge will be shown before you confirm payment.
                        </p>
                    </section>

                    {{-- 6. Changes and cancellations --}}
                    <section aria-labelledby="section-changes-cancellations" class="space-y-3">
                        <h2 id="section-changes-cancellations" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            6. Changes and cancellations
                        </h2>
                        <p>
                            Contact us promptly if you need to change or cancel an order. Available options depend on the progress of artwork preparation and production. Your statutory rights are unaffected.
                        </p>
                    </section>

                    {{-- 7. Returns and refunds --}}
                    <section aria-labelledby="section-returns-refunds" class="space-y-3">
                        <h2 id="section-returns-refunds" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            7. Returns and refunds
                        </h2>
                        <p>
                            Please read our <a href="{{ route('returns') }}" class="text-[#a43e20] underline hover:text-[#051e44] transition-colors">Returns and Refunds Policy</a> for details about custom-made items, samples, and faulty or incorrect products. Nothing in these terms limits your statutory rights.
                        </p>
                    </section>

                    {{-- 8. Uploaded content --}}
                    <section aria-labelledby="section-uploaded-content" class="space-y-3">
                        <h2 id="section-uploaded-content" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            8. Uploaded content
                        </h2>
                        <p>
                            Only upload artwork, logos, and other content that you own or have permission to use. You authorise us to use that content to fulfil your order.
                        </p>
                    </section>

                    {{-- 9. Your account --}}
                    <section aria-labelledby="section-your-account" class="space-y-3">
                        <h2 id="section-your-account" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            9. Your account
                        </h2>
                        <p>
                            Keep your account details accurate and your password private. Contact us if you suspect unauthorised access.
                        </p>
                    </section>

                    {{-- 10. Privacy --}}
                    <section aria-labelledby="section-privacy" class="space-y-3">
                        <h2 id="section-privacy" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            10. Privacy
                        </h2>
                        <p>
                            Our <a href="{{ route('privacy') }}" class="text-[#a43e20] underline hover:text-[#051e44] transition-colors">Privacy Policy</a> explains how we handle personal information.
                        </p>
                    </section>

                    {{-- 11. Contact us --}}
                    <section aria-labelledby="section-contact-us" class="space-y-3">
                        <h2 id="section-contact-us" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            11. Contact us
                        </h2>
                        <p>
                            For questions about these terms or an order, please visit our <a href="{{ route('contact') }}" class="text-[#a43e20] underline hover:text-[#051e44] transition-colors">Contact Us</a> page.
                        </p>
                    </section>

                    {{-- Back to top --}}
                    <div class="pt-2">
                        <a href="#main-content" class="inline-flex items-center text-sm font-medium text-[#a43e20] underline hover:text-[#051e44] transition-colors">
                            &uarr; Back to top
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>

