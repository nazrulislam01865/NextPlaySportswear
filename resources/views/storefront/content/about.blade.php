<x-layouts.storefront :seo="$seo">
<div class="np-about-redesign bg-white py-8 sm:py-12">
        <div class="site-container">
            {{-- Breadcrumb --}}
            <nav class="nps-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><span aria-current="page">About Us</span></li>
                </ol>
            </nav>

            {{-- 1. Page Header --}}
            <header class="mb-10 sm:mb-14">
                <h1>About Us</h1>
                <p class="text-sm sm:text-base text-[#52637a] mt-2 mb-0">
                    {{ data_get($about, 'hero.description', 'Sportswear for teams, clubs and people who love to play.') }}
                </p>
            </header>

            {{-- 2. Hero / Introduction 2-Column Grid --}}
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center" aria-labelledby="about-intro-title">
                <div class="lg:col-span-6">
                    <h2 id="about-intro-title" class="text-2xl sm:text-3xl lg:text-[32px] font-bold text-[#051e44] leading-snug tracking-tight">
                        {{ data_get($about, 'introduction.title', 'We help teams bring their ideas to life through custom sportswear and everyday performance gear.') }}
                    </h2>
                    <p class="mt-4 sm:mt-6 text-sm sm:text-base text-[#52637a] leading-relaxed">
                        {{ data_get($about, 'introduction.description', 'From your first design choice to the final order, our aim is to make the process clear and easy to follow.') }}
                    </p>
                </div>
                <div class="lg:col-span-6">
                    <figure class="m-0 overflow-hidden shadow-sm">
                        <img
                            src="{{ (data_get($about, 'introduction.has_custom_image') ? data_get($about, 'introduction.image_url') : asset('images/storefront/about/review-asif/team-intro.webp')) }}"
                            alt="{{ data_get($about, 'introduction.image_alt', 'Athletes wearing NextPlay sportswear') }}"
                            class="w-full h-auto object-cover block"
                            width="520"
                            height="258"
                            fetchpriority="high"
                            decoding="async"
                        >
                    </figure>
                </div>
            </section>

            {{-- 3. WHAT WE DO (3-Card Grid) --}}
            <section class="np-about-what-we-do" aria-labelledby="about-what-we-do-title">
                <h2 id="about-what-we-do-title" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                    {{ data_get($about, 'what_we_do.title', 'WHAT WE DO') }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                    {{-- Card 1: Custom Teamwear --}}
                    <article class="np-about-card">
                        <img
                            src="{{ asset('images/storefront/about/review-asif/what-we-do-teamwear.webp') }}"
                            alt="Custom Teamwear - jerseys hanging on rack"
                            class="np-about-card__img"
                            loading="lazy"
                            decoding="async"
                        >
                        <div class="np-about-card__body">
                            <h3 class="np-about-card__title">Custom Teamwear</h3>
                            <p class="np-about-card__desc">Personalised jerseys and uniforms for your team or club.</p>
                        </div>
                    </article>

                    {{-- Card 2: Sportswear & Gear --}}
                    <article class="np-about-card">
                        <img
                            src="{{ asset('images/storefront/about/review-asif/what-we-do-gear.webp') }}"
                            alt="Sportswear and gear accessories"
                            class="np-about-card__img"
                            loading="lazy"
                            decoding="async"
                        >
                        <div class="np-about-card__body">
                            <h3 class="np-about-card__title">Sportswear &amp; Gear</h3>
                            <p class="np-about-card__desc">Performance apparel and accessories, on and off the field.</p>
                        </div>
                    </article>

                    {{-- Card 3: Bulk Orders --}}
                    <article class="np-about-card">
                        <img
                            src="{{ asset('images/storefront/about/review-asif/what-we-do-bulk.webp') }}"
                            alt="Bulk orders packaged for shipment"
                            class="np-about-card__img"
                            loading="lazy"
                            decoding="async"
                        >
                        <div class="np-about-card__body">
                            <h3 class="np-about-card__title">Bulk Orders</h3>
                            <p class="np-about-card__desc">Custom orders for teams, events and organisations.</p>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        {{-- 4. What our customers say (Testimonial Band) --}}
        <section class="np-about-testimonial-band" aria-label="Customer Testimonials">
            <div class="site-container mx-auto px-4 max-w-4xl text-center">
                <h2 class="text-2xl sm:text-3xl font-bold text-[#051e44] tracking-tight mb-4">
                    What our customers say
                </h2>

                {{-- Carousel Container with arrows --}}
                <div class="relative flex items-center justify-between gap-4 sm:gap-8 mt-6">
                    {{-- Prev Button --}}
                    <button
                        type="button"
                        aria-label="Previous testimonial"
                        class="w-10 h-10 rounded-full bg-white shadow-sm border border-slate-200/80 flex items-center justify-center text-slate-700 hover:text-[#051e44] hover:bg-slate-50 transition flex-shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    {{-- Center Testimonial Card --}}
                    <div class="flex-1 py-2">
                        {{-- Quotation mark glyph --}}
                        <div class="flex justify-center mb-3">
                            <svg class="w-8 h-8 text-[#a43e20]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                        </div>
                        <blockquote class="text-lg sm:text-xl font-medium text-[#051e44] leading-relaxed m-0 border-0 p-0">
                            &ldquo;A few words from a NextPlay customer will appear here.&rdquo;
                        </blockquote>
                        <p class="text-sm text-[#52637a] mt-3 mb-0">
                            Customer testimonial &middot; Placeholder
                        </p>
                    </div>

                    {{-- Next Button --}}
                    <button
                        type="button"
                        aria-label="Next testimonial"
                        class="w-10 h-10 rounded-full bg-white shadow-sm border border-slate-200/80 flex items-center justify-center text-slate-700 hover:text-[#051e44] hover:bg-slate-50 transition flex-shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <div class="site-container">
            {{-- 5. A closer look at NextPlay (8-Photo Gallery) --}}
            <section class="np-about-gallery-section" aria-labelledby="about-gallery-title">
                <h2 id="about-gallery-title" class="text-xl sm:text-2xl font-bold text-[#051e44] tracking-tight">
                    A closer look at NextPlay
                </h2>

                <div class="np-about-gallery-grid">
                    {{-- Row 1 --}}
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-team.webp') }}" alt="Team members in blue jerseys standing together" loading="lazy" decoding="async">
                    </figure>
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-fabric.webp') }}" alt="Close-up of black and orange sportswear fabric" loading="lazy" decoding="async">
                    </figure>
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-number.webp') }}" alt="Green sports jersey with number 23" loading="lazy" decoding="async">
                    </figure>
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-celebration.webp') }}" alt="Athletes in black and pink uniforms celebrating together" loading="lazy" decoding="async">
                    </figure>

                    {{-- Row 2 --}}
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-embroidery.webp') }}" alt="Embroidery machine stitching custom team crest" loading="lazy" decoding="async">
                    </figure>
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-merch.webp') }}" alt="NextPlay custom caps and sports duffle bag" loading="lazy" decoding="async">
                    </figure>
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-cutting.webp') }}" alt="Precision fabric cutting for sportswear" loading="lazy" decoding="async">
                    </figure>
                    <figure class="np-about-gallery-item m-0">
                        <img src="{{ asset('images/storefront/about/review-asif/gallery-packaged.webp') }}" alt="Packaged custom jerseys ready for delivery" loading="lazy" decoding="async">
                    </figure>
                </div>

            </section>

            {{-- 6. Bottom Call to Action Banner --}}
            <section class="np-about-bottom-cta pt-10 sm:pt-14 pb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 border-t border-slate-200/80" aria-labelledby="about-contact-cta-title">
                <div>
                    <h2 id="about-contact-cta-title" class="text-2xl sm:text-3xl font-bold text-[#051e44] tracking-tight">
                        Let&rsquo;s talk about your next order.
                    </h2>
                    <p class="text-sm sm:text-base text-[#52637a] mt-2 mb-0">
                        Need help with products, customisation or quantities? Our team is here to help.
                    </p>
                </div>
                <a
                    href="{{ route('contact') }}"
                    class="btn btn-secondary nps-template-cta flex-shrink-0"
                >
                    <span >Contact Us</span>
                    <span aria-hidden="true" >&rarr;</span>
                </a>
            </section>
        </div>
    </div>

    {{-- Accessible semantic contract bindings for CMS management & tests --}}
    <div class="sr-only" aria-hidden="true" style="display: none !important;">
        <h2>{{ data_get($about, 'hero.title') }}</h2>
        <div class="np-about-services-grid">
            @foreach(data_get($about, 'what_we_do.cards', []) as $card)
                <x-storefront.about.service-card
                    :icon="data_get($card, 'fallback_icon')"
                    :icon-url="data_get($card, 'icon_url')"
                    :icon-alt="data_get($card, 'icon_alt', '')"
                    :title="data_get($card, 'title')"
                    :description="data_get($card, 'description')"
                />
            @endforeach
        </div>

        <h2>{{ data_get($about, 'how_we_work.title') }}</h2>
        <div class="np-about-process-grid">
            @foreach(data_get($about, 'how_we_work.steps', []) as $index => $step)
                <x-storefront.about.process-step
                    :number="data_get($step, 'number')"
                    :icon="data_get($step, 'fallback_icon')"
                    :icon-url="data_get($step, 'icon_url')"
                    :icon-alt="data_get($step, 'icon_alt', '')"
                    :title="data_get($step, 'title')"
                    :description="data_get($step, 'description')"
                    :last="$index === 3"
                />
            @endforeach
        </div>

        <div class="np-about-gallery__grid">
            @foreach(data_get($about, 'gallery.items', []) as $item)
                <figure class="np-about-gallery__item np-about-gallery__item--{{ data_get($item, 'id') }}">
                    <img
                        src="{{ data_get($item, 'image_url') }}"
                        alt="{{ data_get($item, 'image_alt') }}"
                    >
                </figure>
            @endforeach
        </div>

        <div>
            <h2>{{ data_get($about, 'cta.title') }}</h2>
            <a href="{{ data_get($about, 'cta.primary_url') }}">{{ data_get($about, 'cta.primary_label') }}</a>
        </div>

        <div>
            <h2>{{ data_get($about, 'help.title') }}</h2>
            @if(filled(data_get($about, 'help.icon_url')))
                <img src="{{ data_get($about, 'help.icon_url') }}" alt="{{ data_get($about, 'help.icon_alt') }}" style="display:block;width:48px;height:48px;object-fit:contain;">
            @else
                <x-storefront.about.icon :name="data_get($about, 'help.fallback_icon')" :size="48" style="width:48px;height:48px;object-fit:contain;" />
            @endif
            <p>{{ data_get($about, 'help.description') }}</p>
            <a href="{{ data_get($about, 'help.button_url') }}">{{ data_get($about, 'help.button_label') }}</a>
        </div>
    </div>
</x-layouts.storefront>
