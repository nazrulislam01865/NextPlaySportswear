<x-layouts.storefront :seo="$seo">
    <main class="np-shipping-delivery-page">
        <section class="np-shipping-delivery-hero">
            <div class="np-shipping-delivery-container">
                <p class="np-shipping-delivery-eyebrow">{{ data_get($shippingDelivery, 'hero.eyebrow') }}</p>
                <h1>{{ data_get($shippingDelivery, 'hero.title') }}</h1>
                <p class="np-shipping-delivery-hero__subtitle">{{ data_get($shippingDelivery, 'hero.subtitle') }}</p>
            </div>
        </section>

        <section class="np-shipping-delivery-body">
            <div class="np-shipping-delivery-container">
                <x-storefront.shipping-delivery.tab-bar :tabs="data_get($shippingDelivery, 'tabs', [])" />

                <header class="np-shipping-delivery-intro">
                    <h2>{{ data_get($shippingDelivery, 'delivery_intro.title') }}</h2>
                    <p>{{ data_get($shippingDelivery, 'delivery_intro.subtitle') }}</p>
                </header>

                <div class="np-shipping-delivery-info-grid">
                    @foreach(data_get($shippingDelivery, 'info_cards.cards', []) as $card)
                        <x-storefront.shipping-delivery.info-card :card="$card" />
                    @endforeach
                </div>

                <section class="np-shipping-delivery-timeline-card" aria-labelledby="shipping-delivery-timeline-title">
                    <h2 id="shipping-delivery-timeline-title">{{ data_get($shippingDelivery, 'delivery_steps.title') }}</h2>
                    <div class="np-shipping-delivery-timeline">
                        @foreach(data_get($shippingDelivery, 'delivery_steps.steps', []) as $step)
                            <x-storefront.shipping-delivery.timeline-step :step="$step" />
                        @endforeach
                    </div>
                    <x-storefront.shipping-delivery.notice :notice="data_get($shippingDelivery, 'notice', [])" />
                </section>

                <div class="np-shipping-delivery-main-grid">
                    <section class="np-shipping-delivery-panel np-shipping-delivery-questions" aria-labelledby="shipping-delivery-questions-title">
                        <header class="np-shipping-delivery-panel__header">
                            <h2 id="shipping-delivery-questions-title">{{ data_get($shippingDelivery, 'faqs.title') }}</h2>
                            <p>{{ data_get($shippingDelivery, 'faqs.subtitle') }}</p>
                        </header>
                        <div class="np-shipping-delivery-faq-list">
                            @foreach(data_get($shippingDelivery, 'faqs.items', []) as $faq)
                                <x-storefront.shipping-delivery.faq-row :faq="$faq" />
                            @endforeach
                        </div>
                    </section>

                    <section class="np-shipping-delivery-panel np-shipping-delivery-checklist" aria-labelledby="shipping-delivery-checklist-title">
                        <header class="np-shipping-delivery-panel__header">
                            <h2 id="shipping-delivery-checklist-title">{{ data_get($shippingDelivery, 'address_checklist.title') }}</h2>
                            <p>{{ data_get($shippingDelivery, 'address_checklist.subtitle') }}</p>
                        </header>
                        <div class="np-shipping-delivery-checklist-list">
                            @foreach(data_get($shippingDelivery, 'address_checklist.items', []) as $item)
                                <x-storefront.shipping-delivery.checklist-row :item="$item" />
                            @endforeach
                        </div>
                    </section>
                </div>

                <section class="np-shipping-delivery-cta">
                    <div class="np-shipping-delivery-cta__copy">
                        <h2>{{ data_get($shippingDelivery, 'cta.title') }}</h2>
                        <p>{{ data_get($shippingDelivery, 'cta.description') }}</p>
                    </div>
                    <div class="np-shipping-delivery-cta__actions">
                        <a href="{{ data_get($shippingDelivery, 'cta.primary_url') }}" class="btn btn-primary np-shipping-delivery-cta__button">{{ data_get($shippingDelivery, 'cta.primary_label') }}</a>
                        <a href="{{ data_get($shippingDelivery, 'cta.policy_url') }}" class="np-shipping-delivery-cta__policy">{{ data_get($shippingDelivery, 'cta.policy_label') }}</a>
                    </div>
                </section>
            </div>
        </section>
    </main>
</x-layouts.storefront>
