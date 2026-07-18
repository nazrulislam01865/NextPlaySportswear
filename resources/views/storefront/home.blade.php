<x-layouts.storefront :seo="$seo" :structured-data="$structuredData ?? []">
    <div class="home-page">
        @foreach($homeSections ?? [] as $section)
            @switch($section['component'] ?? $section['key'] ?? '')
                @case('slider')
                    <x-storefront.homepage-slider :slides="$slides" />
                    @break

                @case('hero')
                    <x-storefront.home.hero :section="$section" />
                    @break

                @case('categories')
                    <x-storefront.home.category-section :categories="$categories" :section="$section" />
                    @break

                @case('buyer_paths')
                    <x-storefront.home.buyer-paths :buyer-paths="$section['items'] ?? $buyerPaths ?? []" :section="$section" />
                    @break

                @case('popular_categories')
                    <x-storefront.home.popular-categories :categories="$popularSportsCategories ?? []" :section="$section" />
                    @break

                @case('design_jersey')
                    <x-storefront.home.design-jersey :section="$section" />
                    @break

                @case('bulk_order')
                    <x-storefront.home.bulk-order :section="$section" />
                    @break

                @case('process')
                    <x-storefront.home.process :steps="$section['items'] ?? $processSteps" :section="$section" />
                    @break

                @case('featured_products')
                    <x-storefront.home.featured-products :products="$featuredProducts" :section="$section" />
                    @break

                @case('latest_products')
                    <x-storefront.home.latest-products :products="$latestProducts ?? []" :section="$section" />
                    @break

                @case('best_selling_products')
                    <x-storefront.home.best-selling-products :products="$bestSellingProducts ?? []" :section="$section" />
                    @break

                @case('best_selling_gear')
                    <x-storefront.home.best-selling-gear :categories="$bestSellingGearCategories ?? $categories" :section="$section" />
                    @break

                @case('shop_by_sport')
                    <x-storefront.home.shop-by-sport :sports="$sports" :section="$section" />
                    @break


                @case('why_choose')
                    <x-storefront.home.why-choose :section="$section" />
                    @break

                @case('use_cases')
                    <x-storefront.home.use-cases :section="$section" />
                    @break

                @case('testimonials')
                    <x-storefront.home.testimonials :section="$section" />
                    @break

                @case('faq')
                    <x-storefront.home.faq :faqs="$faqs" :section="$section" />
                    @break
            @endswitch
        @endforeach

        <x-storefront.home.newsletter-signup />
    </div>
</x-layouts.storefront>
