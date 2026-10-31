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
    <style>
        .np-legal-document {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            color: var(--nps-text, #26364b);
        }
        .np-legal-document .nps-breadcrumb {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: var(--nps-muted, #52637a);
            margin-bottom: 1.5rem;
        }
        .np-legal-document .nps-breadcrumb ol {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            padding: 0;
            margin: 0;
        }
        .np-legal-document .nps-breadcrumb li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .np-legal-document .nps-breadcrumb li + li::before {
            content: "/";
            color: #8a99ad;
        }
        .np-legal-document .nps-breadcrumb a {
            color: var(--nps-muted, #52637a);
            text-decoration: none;
            min-height: auto;
            display: inline-flex;
            align-items: center;
            transition: color 0.15s ease;
        }
        .np-legal-document .nps-breadcrumb a:hover {
            color: var(--nps-navy, #051e44);
            text-decoration: underline;
        }
        .np-legal-document .nps-breadcrumb [aria-current] {
            color: var(--nps-muted, #52637a);
            font-weight: 400;
        }
        .np-legal-document h1 {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 2.375rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.025em;
            color: var(--nps-navy, #051e44);
            margin: 0 0 0.5rem 0;
        }
        .np-legal-document .np-legal-subtitle {
            font-size: 0.875rem;
            color: var(--nps-muted, #52637a);
            margin: 0 0 1.25rem 0;
        }
        .np-legal-document .np-legal-intro {
            font-size: 1.0625rem;
            line-height: 1.6;
            color: var(--nps-text, #26364b);
            margin: 0 0 2.25rem 0;
        }
        .np-legal-document section {
            margin-bottom: 2.25rem;
        }
        .np-legal-document h2 {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 1.375rem;
            font-weight: 700;
            line-height: 1.35;
            letter-spacing: -0.015em;
            color: var(--nps-navy, #051e44);
            margin: 0 0 0.625rem 0;
        }
        .np-legal-document p {
            font-size: 1rem;
            line-height: 1.65;
            color: var(--nps-text, #26364b);
            margin: 0 0 0.75rem 0;
        }
        .np-legal-document p:last-child {
            margin-bottom: 0;
        }
        .np-legal-document a:not(.nps-breadcrumb a) {
            color: var(--nps-link, #a43e20);
            text-decoration: underline;
            text-underline-offset: 0.2em;
            transition: color 0.15s ease;
        }
        .np-legal-document a:not(.nps-breadcrumb a):hover {
            color: var(--nps-navy, #051e44);
        }
        .np-legal-document .np-back-to-top {
            display: inline-flex;
            align-items: center;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--nps-link, #a43e20);
            text-decoration: underline;
            text-underline-offset: 0.2em;
            margin-top: 0.75rem;
            transition: color 0.15s ease;
        }
        .np-legal-document .np-back-to-top:hover {
            color: var(--nps-navy, #051e44);
        }
        @media (max-width: 640px) {
            .np-legal-document h1 {
                font-size: 1.75rem;
            }
            .np-legal-document h2 {
                font-size: 1.25rem;
            }
            .np-legal-document .np-legal-intro {
                font-size: 1rem;
            }
            .np-legal-document section {
                margin-bottom: 1.75rem;
            }
        }
    </style>

    <div class="bg-white py-8 sm:py-12 lg:py-14">
        <div class="site-container">
            <div class="np-legal-document max-w-3xl">
                {{-- Breadcrumb --}}
                <nav class="nps-breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><span aria-current="page">Privacy Policy</span></li>
                    </ol>
                </nav>

                {{-- Page Header --}}
                <header>
                    <h1>Privacy Policy</h1>
                    <p class="np-legal-subtitle">
                        Last updated: {{ $updated }}
                    </p>
                    <p class="np-legal-intro">
                        This policy explains how NextPlay Sportswear handles your personal information when you use our website and services.
                    </p>
                </header>

                {{-- Policy Sections --}}
                <div class="np-legal-sections">
                    {{-- 1. Information we collect --}}
                    <section aria-labelledby="section-info-collect">
                        <h2 id="section-info-collect">1. Information we collect</h2>
                        <p>
                            We may collect your contact details, account information, shipping and billing addresses, order details, customisation instructions, uploaded artwork, support messages, and website usage data.
                        </p>
                        <p>
                            Payments are processed by our payment provider. Payment records may include transaction references, payment status, and masked card details.
                        </p>
                    </section>

                    {{-- 2. How we use information --}}
                    <section aria-labelledby="section-how-we-use">
                        <h2 id="section-how-we-use">2. How we use information</h2>
                        <p>
                            We use information to process orders and quotes, prepare artwork, deliver products, provide support, send service updates, prevent fraud, and improve our website.
                        </p>
                    </section>

                    {{-- 3. How information is shared --}}
                    <section aria-labelledby="section-info-shared">
                        <h2 id="section-info-shared">3. How information is shared</h2>
                        <p>
                            We may share relevant information with service providers supporting payments, shipping, hosting, email, analytics, fraud prevention, and production. We do not sell personal information.
                        </p>
                    </section>

                    {{-- 4. Retention and security --}}
                    <section aria-labelledby="section-retention-security">
                        <h2 id="section-retention-security">4. Retention and security</h2>
                        <p>
                            We retain information for as long as reasonably needed for orders, support, accounting, legal compliance, and security.
                        </p>
                    </section>

                    {{-- 5. Your choices and rights --}}
                    <section aria-labelledby="section-choices-rights">
                        <h2 id="section-choices-rights">5. Your choices and rights</h2>
                        <p>
                            Depending on your location, you may have rights to access, correct, delete, restrict, or receive a copy of your personal information. You can opt out of non-essential marketing messages.
                        </p>
                    </section>

                    {{-- 6. Children's privacy --}}
                    <section aria-labelledby="section-childrens-privacy">
                        <h2 id="section-childrens-privacy">6. Children's privacy</h2>
                        <p>
                            The storefront is not directed to children under 13. Orders for youth teams should be managed by an adult or authorised organisation representative.
                        </p>
                    </section>

                    {{-- 7. Contact us about privacy --}}
                    <section aria-labelledby="section-contact-privacy">
                        <h2 id="section-contact-privacy">7. Contact us about privacy</h2>
                        <p>
                            For questions or requests about your personal information, please visit our <a href="{{ route('contact') }}">Contact Us</a> page.
                        </p>
                    </section>

                    {{-- Back to top --}}
                    <div>
                        <a href="#main-content" class="np-back-to-top">
                            &uarr; Back to top
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
