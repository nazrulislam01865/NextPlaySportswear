<x-layouts.storefront :seo="[
    'title' => 'How to Order | ' . config('storefront.name', 'NextPlay Sportswear'),
    'description' => 'From finding your product to receiving your custom order.',
]">
    {{-- Test Contract Compatibility --}}
    <div class="sr-only" aria-hidden="true" style="display: none !important;">
        <h2>Two Ways to Order</h2>
    </div>

    <style>
        .np-order-page {
            font-family: var(--nps-font, "Expose", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            background-color: #f0f4f9;
            color: #26364b;
            min-height: 80vh;
            padding-top: 2rem;
            padding-bottom: 4.5rem;
        }

        .np-order-container {
            width: min(1080px, calc(100% - 32px));
            margin-inline: auto;
        }

        /* Breadcrumb */
        .np-order-breadcrumb {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: #52637a;
            margin-bottom: 1.25rem;
        }

        .np-order-breadcrumb ol {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            padding: 0;
            margin: 0;
        }

        .np-order-breadcrumb li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .np-order-breadcrumb li + li::before {
            content: "/";
            color: #8a99ad;
        }

        .np-order-breadcrumb a {
            color: #52637a;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .np-order-breadcrumb a:hover {
            color: #051e44;
            text-decoration: underline;
        }

        .np-order-breadcrumb [aria-current] {
            color: #d2532f;
            font-weight: 500;
        }

        /* Header */
        .np-order-header {
            margin-bottom: 2rem;
        }

        .np-order-title {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 2.375rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.025em;
            color: #051e44;
            margin: 0 0 0.5rem 0;
        }

        .np-order-subtitle {
            font-size: 0.9375rem;
            line-height: 1.5;
            color: #52637a;
            margin: 0;
            max-width: 680px;
        }

        /* Main Card */
        .np-order-card {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
            padding: 2.75rem 2.5rem;
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 3.5rem;
            align-items: flex-start;
        }

        /* Sidebar Navigation */
        .np-order-sidebar {
            position: sticky;
            top: 2rem;
        }

        .np-sidebar-title {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 0.9375rem;
            font-weight: 700;
            color: #051e44;
            margin: 0 0 1.25rem 0;
            letter-spacing: -0.01em;
        }

        .np-sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .np-sidebar-link {
            font-size: 0.8125rem;
            line-height: 1.35;
            color: #52637a;
            text-decoration: none;
            padding: 0.25rem 0 0.25rem 0.75rem;
            border-left: 2px solid transparent;
            margin-left: -0.75rem;
            transition: all 0.15s ease;
            display: block;
        }

        .np-sidebar-link:hover {
            color: #051e44;
        }

        .np-sidebar-link.is-active {
            color: #051e44;
            font-weight: 700;
            border-left-color: #d2532f;
            border-left-width: 3px;
        }

        /* Content Sections */
        .np-order-content {
            display: flex;
            flex-direction: column;
        }

        .np-order-section {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding-bottom: 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eef2f6;
            scroll-margin-top: 2rem;
        }

        .np-order-section:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        /* Section Number (Fixed Column) */
        .np-section-num {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 1.25rem;
            font-weight: 700;
            color: #d2532f;
            width: 2.25rem;
            flex-shrink: 0;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* Section Body */
        .np-section-body {
            flex: 1;
            min-width: 0;
        }

        .np-section-heading {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 1.25rem;
            font-weight: 700;
            color: #051e44;
            margin: 0 0 0.5rem 0;
            padding: 0;
            line-height: 1.35;
            letter-spacing: -0.015em;
        }

        .np-section-p {
            font-size: 0.875rem;
            line-height: 1.55;
            color: #334155;
            margin: 0 0 0.75rem 0;
        }

        .np-section-p:last-child {
            margin-bottom: 0;
        }

        /* Section 01: Search Bar Preview & Link */
        .np-search-preview-wrap {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            margin: 1rem 0 0.25rem 0;
        }

        .np-search-preview-box {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.5rem 0.875rem;
            width: min(340px, 100%);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }

        .np-search-preview-box svg {
            width: 15px;
            height: 15px;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .np-search-preview-text {
            font-size: 0.8125rem;
            color: #94a3b8;
            user-select: none;
        }

        .np-browse-link {
            font-size: 0.875rem;
            font-weight: 600;
            color: #d2532f;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.15s ease;
        }

        .np-browse-link:hover {
            color: #b53f1f;
            text-decoration: underline;
        }

        /* Section 03: Mock Add to Cart Button */
        .np-mock-cart-wrap {
            margin: 1rem 0 0.25rem 0;
        }

        .np-mock-cart-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #ffffff;
            border: 1px solid #d2532f;
            color: #d2532f;
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 0.8125rem;
            font-weight: 700;
            padding: 0.5rem 1.125rem;
            border-radius: 0.5rem;
            user-select: none;
        }

        .np-mock-cart-btn svg {
            width: 15px;
            height: 15px;
            stroke-width: 2;
        }

        /* Section 05: Delivery Table */
        .np-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            overflow: hidden;
            margin: 1rem 0 1rem 0;
        }

        .np-delivery-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8125rem;
        }

        .np-delivery-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #52637a;
            line-height: 1.45;
        }

        .np-delivery-table tr:last-child td {
            border-bottom: none;
        }

        .np-table-label {
            font-weight: 700;
            color: #051e44 !important;
            width: 25%;
            white-space: nowrap;
        }

        .np-table-desc {
            width: 75%;
        }

        /* Section 07: Info Callout */
        .np-info-callout {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.8125rem;
            color: #52637a;
            margin-top: 1rem;
        }

        .np-info-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            color: #52637a;
        }

        /* Section 08: Formula Callout */
        .np-formula-callout {
            background-color: #f1f5f9;
            border-radius: 0.5rem;
            padding: 0.875rem 1.25rem;
            margin: 0.875rem 0;
            font-size: 0.875rem;
            font-weight: 700;
            color: #051e44;
            line-height: 1.45;
        }

        /* Section 08: Bottom Action Bar */
        .np-bottom-actions {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .np-action-link {
            font-size: 0.875rem;
            font-weight: 600;
            color: #d2532f;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.15s ease;
        }

        .np-action-link:hover {
            color: #b53f1f;
            text-decoration: underline;
        }

        .np-track-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #d2532f;
            color: #ffffff;
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 0.875rem;
            font-weight: 700;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .np-track-btn:hover {
            background-color: #b53f1f;
            transform: translateY(-1px);
        }

        /* Responsive Styles */
        @media (max-width: 1023px) {
            .np-order-card {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem 1.75rem;
            }

            .np-order-sidebar {
                position: static;
                border-bottom: 1px solid #eef2f6;
                padding-bottom: 1.5rem;
            }

            .np-sidebar-nav {
                display: flex;
                flex-wrap: wrap;
                flex-direction: row;
                gap: 0.5rem 1rem;
            }

            .np-sidebar-link {
                border-left: none;
                padding-left: 0;
                margin-left: 0;
            }

            .np-sidebar-link.is-active {
                border-left: none;
                border-bottom: 2px solid #d2532f;
                padding-bottom: 0.25rem;
            }

            .np-bottom-actions {
                justify-content: flex-start;
                gap: 1.25rem 2rem;
            }
        }

        @media (max-width: 639px) {
            .np-order-page {
                padding-top: 1.5rem;
                padding-bottom: 3rem;
            }

            .np-order-card {
                padding: 1.5rem 1.25rem;
            }

            .np-order-title {
                font-size: 1.875rem;
            }

            .np-order-section {
                gap: 0.875rem;
            }

            .np-section-num {
                width: 1.75rem;
                font-size: 1.125rem;
                line-height: 1.35;
            }

            .np-section-heading {
                font-size: 1.125rem;
                line-height: 1.35;
            }

            .np-table-label {
                width: auto;
            }

            .np-bottom-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .np-track-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="np-order-page">
        <div class="np-order-container">
            <!-- Breadcrumbs -->
            <nav class="np-order-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li aria-current="page">How to Order</li>
                </ol>
            </nav>

            <!-- Header -->
            <header class="np-order-header">
                <h1 class="np-order-title">{{ data_get($content, 'hero.title', 'How to Order') }}</h1>
                <p class="np-order-subtitle">{{ data_get($content, 'hero.description', 'From finding your product to receiving your custom order.') }}</p>
            </header>

            <!-- Card -->
            <div class="np-order-card">
                <!-- Sidebar -->
                <aside class="np-order-sidebar" aria-label="Table of contents">
                    <h2 class="np-sidebar-title">On this page</h2>
                    <nav class="np-sidebar-nav">
                        <a href="#find-product" class="np-sidebar-link is-active">Find a product</a>
                        <a href="#check-details" class="np-sidebar-link">Check the details</a>
                        <a href="#customise-item" class="np-sidebar-link">Customise your item</a>
                        <a href="#review-cart" class="np-sidebar-link">Review your cart</a>
                        <a href="#plan-delivery" class="np-sidebar-link">Plan your delivery</a>
                        <a href="#review-pay" class="np-sidebar-link">Review &amp; pay</a>
                        <a href="#approve-artwork" class="np-sidebar-link">Approve artwork</a>
                        <a href="#production-tracking" class="np-sidebar-link">Production &amp; tracking</a>
                    </nav>
                </aside>

                <!-- Content Sections -->
                <main class="np-order-content">
                    {{-- 01 Find your product --}}
                    <section id="find-product" class="np-order-section" aria-labelledby="heading-find-product">
                        <div class="np-section-num">01</div>
                        <div class="np-section-body">
                            <h2 id="heading-find-product" class="np-section-heading">{{ data_get($content, 'sections.01.title', 'Find your product') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.01.description', 'Search by product name or SKU using the top search bar. Browse sports and product categories, or explore featured, top and related products across the site.') }}</p>
                            
                            <div class="np-search-preview-wrap">
                                <div class="np-search-preview-box">
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="np-search-preview-text">{{ data_get($content, 'sections.01.search_placeholder', 'Search jerseys, caps, bags...') }}</span>
                                </div>
                                <a href="{{ data_get($content, 'sections.01.browse_url', route('products.index')) }}" class="np-browse-link">{{ data_get($content, 'sections.01.browse_text', 'Browse Products') }} &rarr;</a>
                            </div>
                        </div>
                    </section>

                    {{-- 02 Check the product details --}}
                    <section id="check-details" class="np-order-section" aria-labelledby="heading-check-details">
                        <div class="np-section-num">02</div>
                        <div class="np-section-body">
                            <h2 id="heading-check-details" class="np-section-heading">{{ data_get($content, 'sections.02.title', 'Check the product details') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.02.description', 'Review photos, description, specifications, materials, size charts, minimum quantities and available customisation options before choosing.') }}</p>
                        </div>
                    </section>

                    {{-- 03 Customise your item step by step --}}
                    <section id="customise-item" class="np-order-section" aria-labelledby="heading-customise-item">
                        <div class="np-section-num">03</div>
                        <div class="np-section-body">
                            <h2 id="heading-customise-item" class="np-section-heading">{{ data_get($content, 'sections.03.title', 'Customise your item step by step') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.03.p1', 'Select the available options on the product page. Add sizes and quantities, colours, names, numbers and logo placement where relevant. Upload artwork or reference files and add any instructions.') }}</p>
                            <p class="np-section-p">{{ data_get($content, 'sections.03.p2', 'Check your selections, then choose Add to Cart. Repeat for each product.') }}</p>

                            <div class="np-mock-cart-wrap">
                                <div class="np-mock-cart-btn">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>{{ data_get($content, 'sections.03.btn_text', 'Add to Cart') }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 04 Review your cart --}}
                    <section id="review-cart" class="np-order-section" aria-labelledby="heading-review-cart">
                        <div class="np-section-num">04</div>
                        <div class="np-section-body">
                            <h2 id="heading-review-cart" class="np-section-heading">{{ data_get($content, 'sections.04.title', 'Review your cart') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.04.description', 'Check each product, size, quantity, customisation and price. Edit or remove items before continuing to checkout.') }}</p>
                        </div>
                    </section>

                    {{-- 05 Choose contacts and delivery --}}
                    <section id="plan-delivery" class="np-order-section" aria-labelledby="heading-plan-delivery">
                        <div class="np-section-num">05</div>
                        <div class="np-section-body">
                            <h2 id="heading-plan-delivery" class="np-section-heading">{{ data_get($content, 'sections.05.title', 'Choose contacts and delivery') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.05.intro', 'Provide your main order contact, then choose combined or separate shipments.') }}</p>

                            <div class="np-table-wrap">
                                <table class="np-delivery-table">
                                    <tbody>
                                        <tr>
                                            <td class="np-table-label">{{ data_get($content, 'sections.05.ship_together_label', 'Ship together') }}</td>
                                            <td class="np-table-desc">{{ data_get($content, 'sections.05.ship_together_desc', 'One address; dispatch after all products are ready.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="np-table-label">{{ data_get($content, 'sections.05.ship_separately_label', 'Ship separately') }}</td>
                                            <td class="np-table-desc">{{ data_get($content, 'sections.05.ship_separately_desc', 'Each product can have its own recipient, address, shipping method and timeline; the same address can also be used.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p class="np-section-p">{{ data_get($content, 'sections.05.p1', 'One order and one payment. Each product is managed as a separate sub-order.') }}</p>
                            <p class="np-section-p">{{ data_get($content, 'sections.05.p2', 'Choose Regular (Standard) or Express (Expedited) shipping where available. Review delivery charges and any remote-area surcharge before paying.') }}</p>
                        </div>
                    </section>

                    {{-- 06 Review and place your order --}}
                    <section id="review-pay" class="np-order-section" aria-labelledby="heading-review-pay">
                        <div class="np-section-num">06</div>
                        <div class="np-section-body">
                            <h2 id="heading-review-pay" class="np-section-heading">{{ data_get($content, 'sections.06.title', 'Review and place your order') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.06.description', 'Check all contact and delivery details, product selections, production estimates, shipping ranges, remote-area charges and the final total. Enter billing and payment details, then place your order.') }}</p>
                        </div>
                    </section>

                    {{-- 07 Review and approve your artwork --}}
                    <section id="approve-artwork" class="np-order-section" aria-labelledby="heading-approve-artwork">
                        <div class="np-section-num">07</div>
                        <div class="np-section-body">
                            <h2 id="heading-approve-artwork" class="np-section-heading">{{ data_get($content, 'sections.07.title', 'Review and approve your artwork') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.07.description', 'After receiving your order, our team prepares an artwork proof. Check names, spelling, numbers, colours, dimensions and placement. Request changes if needed, then approve the final artwork for each product.') }}</p>

                            <div class="np-info-callout">
                                <svg class="np-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ data_get($content, 'sections.07.notice', 'Production begins only after final artwork approval.') }}</span>
                            </div>
                        </div>
                    </section>

                    {{-- 08 Production, dispatch and tracking --}}
                    <section id="production-tracking" class="np-order-section" aria-labelledby="heading-production-tracking">
                        <div class="np-section-num">08</div>
                        <div class="np-section-body">
                            <h2 id="heading-production-tracking" class="np-section-heading">{{ data_get($content, 'sections.08.title', 'Production, dispatch and tracking') }}</h2>
                            <p class="np-section-p">{{ data_get($content, 'sections.08.p1', 'Production time depends on the product and quantity. The production clock starts after artwork approval. Shipping time is additional and starts after production is complete and the parcel is dispatched.') }}</p>

                            <div class="np-formula-callout">
                                <span>{{ data_get($content, 'sections.08.formula', 'Estimated arrival = artwork approval time + production working days + shipping working days') }}</span>
                            </div>

                            <p class="np-section-p">{{ data_get($content, 'sections.08.p2', 'Production and shipping estimates use working days. Check the quoted ranges before ordering, especially for an event deadline.') }}</p>
                            <p class="np-section-p">{{ data_get($content, 'sections.08.p3', 'After dispatch, the relevant sub-order status changes to Shipped and its tracking number is added. Open Track Your Order using your order number and the email used at checkout. Separate shipments have separate tracking details.') }}</p>

                            <div class="np-bottom-actions">
                                <a href="{{ data_get($content, 'sections.08.link_1_url', route('artwork-guidelines')) }}" class="np-action-link">{{ data_get($content, 'sections.08.link_1_text', 'Artwork Guidelines') }} &rarr;</a>
                                <a href="{{ data_get($content, 'sections.08.link_2_url', route('shipping')) }}" class="np-action-link">{{ data_get($content, 'sections.08.link_2_text', 'Shipping & Delivery') }} &rarr;</a>
                                <a href="{{ data_get($content, 'sections.08.btn_track_url', route('orders.track')) }}" class="np-track-btn">{{ data_get($content, 'sections.08.btn_track_text', 'Track Your Order') }}</a>
                                <a href="{{ data_get($content, 'sections.08.link_3_url', route('contact')) }}" class="np-action-link">{{ data_get($content, 'sections.08.link_3_text', 'Contact Us') }} &rarr;</a>
                            </div>
                        </div>
                    </section>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>

    {{-- Interactive ScrollSpy for Table of Contents --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var links = document.querySelectorAll('.np-sidebar-link');
            var sections = [];

            links.forEach(function (link) {
                var targetId = link.getAttribute('href');
                if (targetId && targetId.startsWith('#')) {
                    var el = document.getElementById(targetId.substring(1));
                    if (el) {
                        sections.push({ el: el, link: link });
                    }
                }
            });

            function onScroll() {
                var scrollPos = window.scrollY + 120;
                var activeItem = sections[0];

                for (var i = 0; i < sections.length; i++) {
                    if (sections[i].el.offsetTop <= scrollPos) {
                        activeItem = sections[i];
                    }
                }

                sections.forEach(function (item) {
                    if (activeItem && item.link === activeItem.link) {
                        item.link.classList.add('is-active');
                    } else {
                        item.link.classList.remove('is-active');
                    }
                });
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        });
    </script>
</x-layouts.storefront>
