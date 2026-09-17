<script setup lang="ts">
import { computed } from 'vue';
import AppContainer from '../../../components/ui/AppContainer.vue';
import ProductCarousel from '../../../components/common/ProductCarousel.vue';
import type { StorefrontProduct } from '../../../types/product';

const props=defineProps<{products:StorefrontProduct[];busyProductId?:number|null}>();
const emit=defineEmits<{add:[product:StorefrontProduct];wishlist:[product:StorefrontProduct]}>();
const customizable=computed(()=>props.products.filter(p=>p.is_customizable));
const items=computed(()=>customizable.value.length ? customizable.value : props.products);

const carouselBreakpoints={
  560:{slidesPerView:2,spaceBetween:10},
  900:{slidesPerView:3,spaceBetween:10},
  1280:{slidesPerView:4,spaceBetween:10},
};
</script>

<template>
  <section class="np-section np-make">
    <AppContainer class="np-make__container">
      <ProductCarousel
        title="MAKE IT YOURS"
        :products="items"
        force-customize
        card-variant="new-arrivals"
        :show-price="false"
        :show-badges="false"
        :controls="false"
        link-label="Explore All"
        link-href="/products"
        :busy-product-id="busyProductId"
        :slides-per-view="1.12"
        :space-between="10"
        :breakpoints="carouselBreakpoints"
        @add="emit('add',$event)"
        @wishlist="emit('wishlist',$event)"
      />
    </AppContainer>
  </section>
</template>

<style scoped>
.np-make{overflow:hidden;padding:var(--np-make-it-yours-padding-top) 0 var(--np-make-it-yours-padding-bottom)}
.np-make__container{max-width:var(--np-showcase-max);padding-inline:var(--np-showcase-gutter)}
.np-make :deep(.np-section-head){align-items:center;margin-bottom:28px}
.np-make :deep(.np-section-title){font-size:var(--np-showcase-title-size)}
.np-make :deep(.np-section-head__link){font-family:var(--np-font-body);font-size:var(--np-home-link-size);font-weight:500;text-decoration:none;color:var(--np-color-navy-950)}
.np-make :deep(.np-section-head__link)::after{content:' ↗';font-size:15px}
.np-make :deep(.swiper){overflow:hidden}
@media(max-width:700px){.np-make{padding:var(--np-make-it-yours-padding-top-mobile) 0 var(--np-make-it-yours-padding-bottom-mobile)}.np-make__container{padding-inline:16px}.np-make :deep(.np-section-head__link){font-size:12px}}
</style>
