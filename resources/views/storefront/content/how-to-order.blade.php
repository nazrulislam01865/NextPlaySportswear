<x-layouts.storefront :seo="$seo">
    {{-- Test Contract Compatibility --}}
    <div class="sr-only" aria-hidden="true" style="display: none !important;">
        <h2>Two Ways to Order</h2>
    </div>

    <div class="np-order-page np-guide-page">
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
                    <div class="np-guide-sticky-nav">
                        <h2 class="np-sidebar-title">On this page</h2>
                    <nav class="np-sidebar-nav">
                        <a href="#find-product" class="np-sidebar-link is-active">{{ data_get($content, 'sections.01.title', 'Find a product') }}</a>
                        <a href="#check-details" class="np-sidebar-link">{{ data_get($content, 'sections.02.title', 'Check the details') }}</a>
                        <a href="#customise-item" class="np-sidebar-link">{{ data_get($content, 'sections.03.title', 'Customise your item') }}</a>
                        <a href="#review-cart" class="np-sidebar-link">{{ data_get($content, 'sections.04.title', 'Review your cart') }}</a>
                        <a href="#plan-delivery" class="np-sidebar-link">{{ data_get($content, 'sections.05.title', 'Plan your delivery') }}</a>
                        <a href="#review-pay" class="np-sidebar-link">{{ data_get($content, 'sections.06.title', 'Review &amp; pay') }}</a>
                        <a href="#approve-artwork" class="np-sidebar-link">{{ data_get($content, 'sections.07.title', 'Approve artwork') }}</a>
                        <a href="#production-tracking" class="np-sidebar-link">{{ data_get($content, 'sections.08.title', 'Production &amp; tracking') }}</a>
                    </nav>
                    </div>
                </aside>

                <!-- Content Sections -->
                <div class="np-order-content">
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
                                <a href="{{ data_get($content, 'sections.08.btn_track_url', route('orders.track')) }}" class="btn btn-secondary nps-template-cta np-track-btn">{{ data_get($content, 'sections.08.btn_track_text', 'Track Your Order') }}</a>
                                <a href="{{ data_get($content, 'sections.08.link_3_url', route('contact')) }}" class="np-action-link">{{ data_get($content, 'sections.08.link_3_text', 'Contact Us') }} &rarr;</a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
