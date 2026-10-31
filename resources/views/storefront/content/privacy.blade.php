@php
    $siteName = config('storefront.name', 'NextPlay Sportswear');
    $title = $title ?? 'Privacy Policy';
    $updated = $updated ?? '22 June 2026';
    $description = $description ?? 'This policy explains how NextPlay Sportswear handles your personal information when you use our website and services.';
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
                        <li><span aria-current="page">Privacy Policy</span></li>
                    </ol>
                </nav>

                {{-- Page Header --}}
                <header class="mb-8 sm:mb-10">
                    <h1 class="text-3xl font-bold tracking-tight text-[#051e44] sm:text-4xl lg:text-[40px] leading-tight">
                        Privacy Policy
                    </h1>
                    <p class="mt-2 text-sm text-[#52637a]">
                        Last updated: {{ $updated }}
                    </p>
                    <p class="mt-5 text-base sm:text-lg leading-relaxed text-[#26364b]">
                        This policy explains how NextPlay Sportswear handles your personal information when you use our website and services.
                    </p>
                </header>

                {{-- Policy Sections --}}
                <div class="space-y-8 sm:space-y-10 text-base leading-relaxed text-[#26364b]">
                    {{-- 1. Information we collect --}}
                    <section aria-labelledby="section-info-collect" class="space-y-3">
                        <h2 id="section-info-collect" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            1. Information we collect
                        </h2>
                        <p>
                            We may collect your contact details, account information, shipping and billing addresses, order details, customisation instructions, uploaded artwork, support messages, and website usage data.
                        </p>
                        <p>
                            Payments are processed by our payment provider. Payment records may include transaction references, payment status, and masked card details.
                        </p>
                    </section>

                    {{-- 2. How we use information --}}
                    <section aria-labelledby="section-how-we-use" class="space-y-3">
                        <h2 id="section-how-we-use" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            2. How we use information
                        </h2>
                        <p>
                            We use information to process orders and quotes, prepare artwork, deliver products, provide support, send service updates, prevent fraud, and improve our website.
                        </p>
                    </section>

                    {{-- 3. How information is shared --}}
                    <section aria-labelledby="section-info-shared" class="space-y-3">
                        <h2 id="section-info-shared" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            3. How information is shared
                        </h2>
                        <p>
                            We may share relevant information with service providers supporting payments, shipping, hosting, email, analytics, fraud prevention, and production. We do not sell personal information.
                        </p>
                    </section>

                    {{-- 4. Retention and security --}}
                    <section aria-labelledby="section-retention-security" class="space-y-3">
                        <h2 id="section-retention-security" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            4. Retention and security
                        </h2>
                        <p>
                            We retain information for as long as reasonably needed for orders, support, accounting, legal compliance, and security.
                        </p>
                    </section>

                    {{-- 5. Your choices and rights --}}
                    <section aria-labelledby="section-choices-rights" class="space-y-3">
                        <h2 id="section-choices-rights" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            5. Your choices and rights
                        </h2>
                        <p>
                            Depending on your location, you may have rights to access, correct, delete, restrict, or receive a copy of your personal information. You can opt out of non-essential marketing messages.
                        </p>
                    </section>

                    {{-- 6. Children's privacy --}}
                    <section aria-labelledby="section-childrens-privacy" class="space-y-3">
                        <h2 id="section-childrens-privacy" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            6. Children's privacy
                        </h2>
                        <p>
                            The storefront is not directed to children under 13. Orders for youth teams should be managed by an adult or authorised organisation representative.
                        </p>
                    </section>

                    {{-- 7. Contact us about privacy --}}
                    <section aria-labelledby="section-contact-privacy" class="space-y-3">
                        <h2 id="section-contact-privacy" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                            7. Contact us about privacy
                        </h2>
                        <p>
                            For questions or requests about your personal information, please visit our <a href="{{ route('contact') }}" class="text-[#a43e20] underline hover:text-[#051e44] transition-colors">Contact Us</a> page.
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

