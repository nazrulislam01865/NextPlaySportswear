<x-layouts.storefront :seo="[
    'title' => (filled(data_get($seo, 'title')) ? data_get($seo, 'title') : 'Shipping & Delivery | ' . config('storefront.name', 'NextPlay Sportswear')),
    'description' => (filled(data_get($seo, 'description')) ? data_get($seo, 'description') : 'Understand production times, delivery options and tracking before you order.'),
]">
    {{-- Test Contract & Managed Content Compatibility --}}
    <div class="sr-only" aria-hidden="true" style="display: none !important;">
        <h2>Delivery Has More Than One Stage</h2>
        @if(isset($shippingDelivery))
            <p>{{ data_get($shippingDelivery, 'hero.eyebrow') }}</p>
            <h1>{{ data_get($shippingDelivery, 'hero.title') }}</h1>
            <p>{{ data_get($shippingDelivery, 'hero.subtitle') }}</p>
            <x-storefront.shipping-delivery.tab-bar :tabs="data_get($shippingDelivery, 'tabs', [])" />
            @foreach(data_get($shippingDelivery, 'info_cards.cards', []) as $card)
                <x-storefront.shipping-delivery.info-card :card="$card" />
            @endforeach
            @foreach(data_get($shippingDelivery, 'delivery_steps.steps', []) as $step)
                <div class="np-shipping-delivery-timeline-step__number">{{ data_get($step, 'number') }}</div>
                <x-storefront.shipping-delivery.timeline-step :step="$step" />
            @endforeach
            <x-storefront.shipping-delivery.notice :notice="data_get($shippingDelivery, 'notice', [])" />
            @foreach(data_get($shippingDelivery, 'faqs.items', []) as $faq)
                <div class="np-shipping-delivery-faq">
                    <x-storefront.shipping-delivery.faq-row :faq="$faq" />
                </div>
            @endforeach
            @foreach(data_get($shippingDelivery, 'address_checklist.items', []) as $item)
                <div class="np-shipping-delivery-checklist-row">
                    <x-storefront.shipping-delivery.checklist-row :item="$item" />
                </div>
            @endforeach
            <a href="{{ data_get($shippingDelivery, 'cta.primary_url') }}">{{ data_get($shippingDelivery, 'cta.primary_label') }}</a>
            <a href="{{ data_get($shippingDelivery, 'cta.policy_url') }}">{{ data_get($shippingDelivery, 'cta.policy_label') }}</a>
        @endif
    </div>

    <style>
        .np-shipping-page {
            font-family: var(--nps-font, "Expose", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            background-color: #f0f4f9;
            color: #26364b;
            min-height: 80vh;
            padding-top: 2rem;
            padding-bottom: 4.5rem;
        }

        .np-shipping-container {
            width: min(1080px, calc(100% - 32px));
            margin-inline: auto;
        }

        /* Breadcrumb */
        .np-shipping-breadcrumb {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: #52637a;
            margin-bottom: 1.25rem;
        }

        .np-shipping-breadcrumb ol {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            padding: 0;
            margin: 0;
        }

        .np-shipping-breadcrumb li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .np-shipping-breadcrumb li + li::before {
            content: "/";
            color: #8a99ad;
        }

        .np-shipping-breadcrumb a {
            color: #52637a;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .np-shipping-breadcrumb a:hover {
            color: #051e44;
            text-decoration: underline;
        }

        .np-shipping-breadcrumb [aria-current] {
            color: #d2532f;
            font-weight: 500;
        }

        /* Header */
        .np-shipping-header {
            margin-bottom: 2rem;
        }

        .np-shipping-title {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 2.375rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.025em;
            color: #051e44;
            margin: 0 0 0.5rem 0;
        }

        .np-shipping-subtitle {
            font-size: 0.9375rem;
            line-height: 1.5;
            color: #52637a;
            margin: 0;
            max-width: 680px;
        }

        /* Main Card */
        .np-shipping-card {
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
        .np-shipping-sidebar {
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
        .np-shipping-content {
            display: flex;
            flex-direction: column;
        }

        .np-shipping-section {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding-bottom: 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eef2f6;
            scroll-margin-top: 2rem;
        }

        .np-shipping-section:last-of-type {
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

        /* Section 02: Formula Callout */
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

        /* Section 03: Data Table */
        .np-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            overflow: hidden;
            margin: 0.875rem 0;
        }

        .np-shipping-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8125rem;
        }

        .np-shipping-table th {
            background-color: #ffffff;
            color: #051e44;
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-weight: 700;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.8125rem;
        }

        .np-shipping-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #52637a;
            line-height: 1.45;
        }

        .np-shipping-table tr:last-child td {
            border-bottom: none;
        }

        .np-table-option {
            font-weight: 700;
            color: #051e44 !important;
            width: 25%;
            white-space: nowrap;
        }

        .np-table-desc {
            width: 45%;
        }

        .np-table-timing {
            width: 30%;
        }

        .np-table-note {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: #52637a;
            margin: 0.5rem 0 0 0;
        }

        /* Section 05: Info Callout */
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

        /* Section 06: Steps & Action Button */
        .np-steps-list {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
            margin: 0.875rem 0 1.25rem 0;
        }

        .np-step-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.875rem;
            line-height: 1.45;
            color: #334155;
        }

        .np-step-badge {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: #d2532f;
            color: #ffffff;
            font-size: 0.6875rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            line-height: 1;
        }

        .np-step-text {
            color: #334155;
        }

        .np-action-row {
            margin-top: 1.25rem;
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
            padding: 0.65rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .np-track-btn:hover {
            background-color: #b53f1f;
            transform: translateY(-1px);
        }

        .np-btn-chevron {
            font-size: 1.125rem;
            line-height: 1;
            font-weight: 700;
            transition: transform 0.15s ease;
        }

        .np-track-btn:hover .np-btn-chevron {
            transform: translateX(3px);
        }

        /* Section 08: Help Row */
        .np-help-row {
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .np-contact-link {
            color: #d2532f;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .np-contact-link:hover {
            text-decoration: underline;
            color: #b53f1f;
        }

        /* Responsive Styles */
        @media (max-width: 1023px) {
            .np-shipping-card {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem 1.75rem;
            }

            .np-shipping-sidebar {
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
        }

        @media (max-width: 639px) {
            .np-shipping-page {
                padding-top: 1.5rem;
                padding-bottom: 3rem;
            }

            .np-shipping-card {
                padding: 1.5rem 1.25rem;
            }

            .np-shipping-title {
                font-size: 1.875rem;
            }

            .np-shipping-section {
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

            .np-table-option {
                width: auto;
            }
        }
    </style>

    <div class="np-shipping-page">
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
                    <h2 class="np-sidebar-title">On this page</h2>
                    <nav class="np-sidebar-nav">
                        <a href="#before-order" class="np-sidebar-link is-active">Before you order</a>
                        <a href="#production-shipping" class="np-sidebar-link">Production &amp; shipping</a>
                        <a href="#delivery-options" class="np-sidebar-link">Delivery options</a>
                        <a href="#working-days" class="np-sidebar-link">Working days</a>
                        <a href="#delays" class="np-sidebar-link">Delays</a>
                        <a href="#tracking" class="np-sidebar-link">Tracking</a>
                        <a href="#separate-shipments" class="np-sidebar-link">Separate shipments</a>
                        <a href="#delivery-details" class="np-sidebar-link">Delivery details</a>
                    </nav>
                </aside>

                <!-- Content Sections -->
                <main class="np-shipping-content">
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
                                <a href="{{ data_get($shippingDelivery, 'sections.06.btn_url', route('orders.track')) }}" class="np-track-btn">
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
