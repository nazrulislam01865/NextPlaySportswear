<script setup lang="ts">
import { computed } from 'vue';
import AppContainer from '../../../components/ui/AppContainer.vue';
import ProductCarousel from '../../../components/common/ProductCarousel.vue';
import type { StorefrontProduct } from '../../../types/product';
import type { HomeSection } from '../types/home.types';
import { sectionTitle } from '../utils/homeSection';

const props = defineProps<{ products: StorefrontProduct[]; busyProductId?: number|null; section?: HomeSection }>();
const title = computed(() => sectionTitle(props.section, 'NEW ARRIVALS'));
const emit = defineEmits<{ add:[product:StorefrontProduct]; wishlist:[product:StorefrontProduct] }>();

const carouselBreakpoints = {
  560: { slidesPerView: 2.05, spaceBetween: 8 },
  860: { slidesPerView: 3.05, spaceBetween: 8 },
  1180: { slidesPerView: 4.05, spaceBetween: 10 },
  1580: { slidesPerView: 5, spaceBetween: 10 },
};
</script>

<template>
  <section class="np-section np-products-section">
    <AppContainer class="np-new-arrivals__container">
      <ProductCarousel
        :title="title"
        :products="products"
        :busy-product-id="busyProductId"
        card-variant="new-arrivals"
        controls-variant="showcase"
        :slides-per-view="1.18"
        :space-between="8"
        :breakpoints="carouselBreakpoints"
        @add="emit('add',$event)"
        @wishlist="emit('wishlist',$event)"
      />
    </AppContainer>
  </section>
</template>

<style scoped>
.np-products-section{
  overflow:hidden;
  padding-top:var(--np-home-new-arrivals-padding-top);
  padding-bottom:52px;
}

.np-new-arrivals__container {
  max-width:min(var(--np-showcase-max), calc(100vw - 24px));
  padding-inline:var(--np-showcase-gutter);
}

.np-products-section :deep(.np-section-head) {
  margin-bottom:28px;
}

.np-products-section :deep(.np-section-title) {
  font-size:var(--np-home-new-arrivals-title-size);
  line-height:var(--np-type-title-3-line-height);
  letter-spacing:0;
}

.np-products-section :deep(.swiper) {
  overflow:hidden;
}

@media (max-width: 700px) {
  .np-products-section { padding-top:42px; padding-bottom:32px; }
  .np-new-arrivals__container { max-width:100%; padding-inline:16px; }
  .np-products-section :deep(.np-section-head) { margin-bottom:18px; }
}
</style>
