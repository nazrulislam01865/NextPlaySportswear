<x-layouts.storefront :seo="$seo" :structured-data="$structuredData ?? []">
    <div class="home-page">
        <x-storefront.homepage-slider :slides="$slides" />
        <x-storefront.home.hero />
        <x-storefront.home.category-section :categories="$categories" />
        <x-storefront.home.buyer-paths :buyer-paths="$buyerPaths ?? []" />
        <x-storefront.home.popular-categories />
        <x-storefront.home.design-jersey />
        <x-storefront.home.bulk-order />
        <x-storefront.home.process :steps="$processSteps" />
        <x-storefront.home.featured-products :products="$featuredProducts" />
        <x-storefront.home.best-selling-gear :categories="$categories" />
        <x-storefront.home.shop-by-sport :sports="$sports" />
        <x-storefront.home.customization-options />
        <x-storefront.home.why-choose />
        <x-storefront.home.use-cases />
        <x-storefront.home.support />
        <x-storefront.home.testimonials />
        <x-storefront.home.faq :faqs="$faqs" />
        <x-storefront.home.final-cta />
    </div>
</x-layouts.storefront>
