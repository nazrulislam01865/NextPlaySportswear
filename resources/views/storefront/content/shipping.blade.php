<x-layouts.storefront :seo="$seo">



    <div class="np-shipping-page np-guide-page">
        <div class="np-shipping-container">
            <!-- Breadcrumbs -->
            <nav class="np-shipping-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li aria-current="page">Shipping &amp; Delivery</li>
                </ol>
            </nav>

            <!-- Header -->
            <header class="np-shipping-header">
                <h1 class="np-shipping-title">{{ data_get($shippingDelivery, 'display_header.title', 'Shipping & Delivery') }}</h1>
                <p class="np-shipping-subtitle">{{ data_get($shippingDelivery, 'display_header.subtitle', 'Understand production times, delivery options and tracking before you order.') }}</p>
            </header>

            <!-- Card -->
            <div class="np-shipping-card">
                <!-- Sidebar -->
                <aside class="np-shipping-sidebar" aria-label="Table of contents">
                    <div class="np-guide-sticky-nav">
                        <h2 class="np-sidebar-title">On this page</h2>
                    <nav class="np-sidebar-nav">
                        <a href="#before-order" class="np-sidebar-link is-active">{{ data_get($shippingDelivery, 'sections.01.title', 'Before you order') }}</a>
                        <a href="#production-shipping" class="np-sidebar-link">Production &amp; shipping</a>
                        <a href="#delivery-options" class="np-sidebar-link">{{ data_get($shippingDelivery, 'sections.03.title', 'Delivery options') }}</a>
                        <a href="#working-days" class="np-sidebar-link">{{ data_get($shippingDelivery, 'sections.04.title', 'Working days') }}</a>
                        <a href="#delays" class="np-sidebar-link">{{ data_get($shippingDelivery, 'sections.05.title', 'Delays') }}</a>
                        <a href="#tracking" class="np-sidebar-link">{{ data_get($shippingDelivery, 'sections.06.title', 'Tracking') }}</a>
                        <a href="#separate-shipments" class="np-sidebar-link">{{ data_get($shippingDelivery, 'sections.07.title', 'Separate shipments') }}</a>
                        <a href="#delivery-details" class="np-sidebar-link">{{ data_get($shippingDelivery, 'sections.08.title', 'Delivery details') }}</a>
                    </nav>
                    </div>
                </aside>

                <!-- Content Sections -->
                <div class="np-shipping-content">
                    {{-- 01 Before you order --}}
                    <section id="before-order" class="np-shipping-section" aria-labelledby="heading-before-order">
                        <div class="np-section-num">01</div>
                        <div class="np-section-body">
                            <h2 id="heading-before-order" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.01.title', 'Before you order') }}</h2>
                            <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.01.p1', 'Check the production estimate, shipping option, delivery range and charges before placing your order.') }}</p>
                            @if(filled(data_get($shippingDelivery, 'sections.01.p2')))
                                <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.01.p2') }}</p>
                            @endif
                        </div>
                    </section>

                    {{-- 02 Production and shipping are separate --}}
                    <section id="production-shipping" class="np-shipping-section" aria-labelledby="heading-production-shipping">
                        <div class="np-section-num">02</div>
                        <div class="np-section-body">
                            <h2 id="heading-production-shipping" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.02.title', 'Production and shipping are separate') }}</h2>
                            <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.02.p1', 'Production starts after artwork approval. Shipping time begins after production is complete and your parcel has been dispatched.') }}</p>
                            
                            <div class="np-formula-callout">
                                <span>{{ data_get($shippingDelivery, 'sections.02.formula', 'Estimated time to receive your order = production working days + shipping working days.') }}</span>
                            </div>

                            @if(filled(data_get($shippingDelivery, 'sections.02.p2')))
                                <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.02.p2') }}</p>
                            @endif
                        </div>
                    </section>

                    {{-- 03 Choose your shipping option --}}
                    <section id="delivery-options" class="np-shipping-section" aria-labelledby="heading-delivery-options">
                        <div class="np-section-num">03</div>
                        <div class="np-section-body">
                            <h2 id="heading-delivery-options" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.03.title', 'Choose your shipping option') }}</h2>
                            
                            <div class="np-table-wrap">
                                <table class="np-shipping-table">
                                    <thead>
                                        <tr>
                                            <th>Option</th>
                                            <th>What to expect</th>
                                            <th>Timing</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(data_get($shippingDelivery, 'sections.03.options', []) as $opt)
                                            <tr>
                                                <td class="np-table-option">{{ $opt['name'] ?? '' }}</td>
                                                <td class="np-table-desc">{{ $opt['what_to_expect'] ?? '' }}</td>
                                                <td class="np-table-timing">{{ $opt['timing'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <p class="np-table-note">{{ data_get($shippingDelivery, 'sections.03.note_1', 'Express shipping speeds up transit; it does not shorten production time.') }}</p>
                            <p class="np-table-note">{{ data_get($shippingDelivery, 'sections.03.note_2', 'The carrier and available services depend on destination and order. Confirmed service details appear with your shipment.') }}</p>
                        </div>
                    </section>

                    {{-- 04 How working days are counted --}}
                    <section id="working-days" class="np-shipping-section" aria-labelledby="heading-working-days">
                        <div class="np-section-num">04</div>
                        <div class="np-section-body">
                            <h2 id="heading-working-days" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.04.title', 'How working days are counted') }}</h2>
                            <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.04.p1', 'Both production and shipping estimates use working days, not calendar days. Weekends and relevant public holidays are excluded. Holiday schedules may differ between the production location, transit hubs and destination.') }}</p>
                        </div>
                    </section>

                    {{-- 05 Why we show a delivery range --}}
                    <section id="delays" class="np-shipping-section" aria-labelledby="heading-delays">
                        <div class="np-section-num">05</div>
                        <div class="np-section-body">
                            <h2 id="heading-delays" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.05.title', 'Why we show a delivery range') }}</h2>
                            <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.05.p1', 'Transit times vary, so we provide an estimated range rather than a single arrival date. Customs checks, weather, peak periods and carrier disruption can cause delays.') }}</p>
                            @if(filled(data_get($shippingDelivery, 'sections.05.p2')))
                                <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.05.p2') }}</p>
                            @endif

                            <div class="np-info-callout">
                                <svg class="np-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ data_get($shippingDelivery, 'sections.05.callout', 'Estimated dates are not guaranteed unless expressly confirmed.') }}</span>
                            </div>
                        </div>
                    </section>

                    {{-- 06 Track your order --}}
                    <section id="tracking" class="np-shipping-section" aria-labelledby="heading-tracking">
                        <div class="np-section-num">06</div>
                        <div class="np-section-body">
                            <h2 id="heading-tracking" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.06.title', 'Track your order') }}</h2>
                            <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.06.p1', 'After dispatch, your order or sub-order status changes to Shipped and a tracking number is added. Carrier tracking may take time to show its first scan.') }}</p>

                            <div class="np-steps-list">
                                @foreach(data_get($shippingDelivery, 'sections.06.steps', []) as $idx => $step)
                                    <div class="np-step-item">
                                        <span class="np-step-badge">{{ $idx + 1 }}</span>
                                        <span class="np-step-text">{{ $step }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="np-action-row">
                                <a href="{{ data_get($shippingDelivery, 'sections.06.btn_url', route('orders.track')) }}" class="btn btn-secondary nps-template-cta btn btn-secondary btn-lg np-track-btn">
                                    <span>{{ data_get($shippingDelivery, 'sections.06.btn_text', 'Track Your Order') }}</span>
                                    <span class="np-btn-chevron" aria-hidden="true">&rsaquo;</span>
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- 07 Orders with multiple products --}}
                    <section id="separate-shipments" class="np-shipping-section" aria-labelledby="heading-separate-shipments">
                        <div class="np-section-num">07</div>
                        <div class="np-section-body">
                            <h2 id="heading-separate-shipments" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.07.title', 'Orders with multiple products') }}</h2>
                            <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.07.p1', 'Products may have different production schedules. Choose separate shipments as items become ready, or combined shipping where offered. Separate shipments can use the same or different recipients and addresses. Each shipment has its own estimated dates, charges and tracking number.') }}</p>
                        </div>
                    </section>

                    {{-- 08 Check your delivery details --}}
                    <section id="delivery-details" class="np-shipping-section" aria-labelledby="heading-delivery-details">
                        <div class="np-section-num">08</div>
                        <div class="np-section-body">
                            <h2 id="heading-delivery-details" class="np-section-heading">{{ data_get($shippingDelivery, 'sections.08.title', 'Check your delivery details') }}</h2>
                            <p class="np-section-p">{{ data_get($shippingDelivery, 'sections.08.p1', 'Provide the correct recipient name, complete address, postcode and contact number. Contact us promptly if something needs changing; changes after dispatch may not be possible.') }}</p>

                            <p class="np-help-row">
                                <span>{{ data_get($shippingDelivery, 'sections.08.help_label', 'Need help with a delivery?') }}</span>
                                <a href="{{ data_get($shippingDelivery, 'sections.08.contact_url', route('contact')) }}" class="np-contact-link">{{ data_get($shippingDelivery, 'sections.08.contact_text', 'Contact us') }} &rarr;</a>
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

</x-layouts.storefront>
