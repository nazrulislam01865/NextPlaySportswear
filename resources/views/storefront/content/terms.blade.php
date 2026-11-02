@php
    $siteName = config('storefront.name', 'NextPlay Sportswear');
    $title = $title ?? 'Terms and Conditions';
    $description = $description ?? 'These terms explain the ordering process and your responsibilities when shopping with NextPlay Sportswear.';
@endphp

<x-layouts.storefront :seo="[
    'title' => $title . ' | ' . $siteName,
    'description' => $description,
]">
<div class="bg-white py-8 sm:py-12 lg:py-14">
        <div class="site-container">
            <div class="np-legal-document mx-auto">
                {{-- Breadcrumb --}}
                <nav class="nps-breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><span aria-current="page">Terms and Conditions</span></li>
                    </ol>
                </nav>

                {{-- Page Header --}}
                <header>
                    <h1>Terms and Conditions</h1>
                    <p class="np-legal-intro">
                        These terms explain the ordering process and your responsibilities when shopping with NextPlay Sportswear.
                    </p>
                </header>

                {{-- Terms Sections --}}
                <div class="np-legal-sections">
                    {{-- 1. About these terms --}}
                    <section aria-labelledby="section-about-terms">
                        <h2 id="section-about-terms">1. About these terms</h2>
                        <p>
                            These terms apply when you browse our website, request a quotation, or place an order. Please read them before ordering.
                        </p>
                    </section>

                    {{-- 2. Product information and pricing --}}
                    <section aria-labelledby="section-product-pricing">
                        <h2 id="section-product-pricing">2. Product information and pricing</h2>
                        <p>
                            Product details, prices, currencies, and available options are shown on the website or in your quotation. Check your selected specifications and order total before payment.
                        </p>
                    </section>

                    {{-- 3. Orders and payment --}}
                    <section aria-labelledby="section-orders-payment">
                        <h2 id="section-orders-payment">3. Orders and payment</h2>
                        <p>
                            Review your contact details, addresses, quantities, and customisation instructions before placing an order. Payment details and the amount due are shown at checkout or in your quotation.
                        </p>
                    </section>

                    {{-- 4. Artwork and customisation --}}
                    <section aria-labelledby="section-artwork-customisation">
                        <h2 id="section-artwork-customisation">4. Artwork and customisation</h2>
                        <p>
                            We will send artwork for your approval before production begins. Check all names, numbers, logos, colours, sizes, and placement carefully. Production starts after artwork approval.
                        </p>
                    </section>

                    {{-- 5. Production and delivery --}}
                    <section aria-labelledby="section-production-delivery">
                        <h2 id="section-production-delivery">5. Production and delivery</h2>
                        <p>
                            Production and delivery estimates are provided with your order. Timing depends on artwork approval, product requirements, and the selected shipping method. Any remote-area charge will be shown before you confirm payment.
                        </p>
                    </section>

                    {{-- 6. Changes and cancellations --}}
                    <section aria-labelledby="section-changes-cancellations">
                        <h2 id="section-changes-cancellations">6. Changes and cancellations</h2>
                        <p>
                            Contact us promptly if you need to change or cancel an order. Available options depend on the progress of artwork preparation and production. Your statutory rights are unaffected.
                        </p>
                    </section>

                    {{-- 7. Returns and refunds --}}
                    <section aria-labelledby="section-returns-refunds">
                        <h2 id="section-returns-refunds">7. Returns and refunds</h2>
                        <p>
                            Please read our <a href="{{ route('returns') }}">Returns and Refunds Policy</a> for details about custom-made items, samples, and faulty or incorrect products. Nothing in these terms limits your statutory rights.
                        </p>
                    </section>

                    {{-- 8. Uploaded content --}}
                    <section aria-labelledby="section-uploaded-content">
                        <h2 id="section-uploaded-content">8. Uploaded content</h2>
                        <p>
                            Only upload artwork, logos, and other content that you own or have permission to use. You authorise us to use that content to fulfil your order.
                        </p>
                    </section>

                    {{-- 9. Your account --}}
                    <section aria-labelledby="section-your-account">
                        <h2 id="section-your-account">9. Your account</h2>
                        <p>
                            Keep your account details accurate and your password private. Contact us if you suspect unauthorised access.
                        </p>
                    </section>

                    {{-- 10. Privacy --}}
                    <section aria-labelledby="section-privacy">
                        <h2 id="section-privacy">10. Privacy</h2>
                        <p>
                            Our <a href="{{ route('privacy') }}">Privacy Policy</a> explains how we handle personal information.
                        </p>
                    </section>

                    {{-- 11. Contact us --}}
                    <section aria-labelledby="section-contact-us">
                        <h2 id="section-contact-us">11. Contact us</h2>
                        <p>
                            For questions about these terms or an order, please visit our <a href="{{ route('contact') }}">Contact Us</a> page.
                        </p>
                    </section>

                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
