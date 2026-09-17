<script setup lang="ts">
import { computed, ref } from 'vue';
import AppContainer from '../../../components/ui/AppContainer.vue';
import ProductCarousel from '../../../components/common/ProductCarousel.vue';
import type { StorefrontProduct } from '../../../types/product';

const props=defineProps<{featured:StorefrontProduct[];bestSelling:StorefrontProduct[];busyProductId?:number|null}>();
const emit=defineEmits<{add:[product:StorefrontProduct];wishlist:[product:StorefrontProduct]}>();
const tab=ref<'featured'|'popular'|'trending'>('featured');

const products=computed(()=>{
  if (tab.value === 'featured') return props.featured.length ? props.featured : props.bestSelling;
  return props.bestSelling.length ? props.bestSelling : props.featured;
});

const carouselBreakpoints={
  560:{slidesPerView:2.05,spaceBetween:8},
  860:{slidesPerView:3.05,spaceBetween:8},
  1180:{slidesPerView:4.05,spaceBetween:10},
  1580:{slidesPerView:5,spaceBetween:10},
};
</script>

<template>
  <section class="np-section np-best">
    <AppContainer class="np-best__container">
      <ProductCarousel
        title="BEST CHOICES FOR YOU"
        :products="products"
        :busy-product-id="busyProductId"
        card-variant="new-arrivals"
        controls-variant="showcase"
        :slides-per-view="1.18"
        :space-between="8"
        :breakpoints="carouselBreakpoints"
        @add="emit('add',$event)"
        @wishlist="emit('wishlist',$event)"
      >
        <template #tabs>
          <div class="np-tabs" role="tablist" aria-label="Best choices filters">
            <button :class="{active:tab==='featured'}" @click="tab='featured'">FEATURED</button>
            <button :class="{active:tab==='popular'}" @click="tab='popular'">POPULAR</button>
            <button :class="{active:tab==='trending'}" @click="tab='trending'">TRENDING</button>
          </div>
        </template>
      </ProductCarousel>
    </AppContainer>
  </section>
</template>

<style scoped>
.np-best{overflow:hidden;padding:62px 0 74px}
.np-best__container{max-width:var(--np-showcase-max);padding-inline:var(--np-showcase-gutter)}
.np-best :deep(.np-section-head){align-items:flex-end;margin-bottom:28px}
.np-best :deep(.np-section-head__left){flex-direction:column;align-items:flex-start;gap:20px}
.np-best :deep(.np-section-title){font-size:var(--np-showcase-title-size)}
.np-best :deep(.swiper){overflow:hidden}
.np-tabs{display:flex;gap:0}
.np-tabs button{min-width:var(--np-best-tabs-min-width);min-height:var(--np-best-tabs-height);padding:0 var(--np-best-tabs-padding-x);background:var(--np-home-control-bg);color:var(--np-color-text-muted);font-family:var(--np-font-display);font-size:var(--np-best-tabs-font-size);font-weight:var(--np-home-tabs-weight);text-transform:uppercase}
.np-tabs button.active{background:var(--np-color-navy-950);color:var(--np-home-on-dark)}
@media(max-width:700px){.np-best{padding:42px 0 50px}.np-best__container{padding-inline:16px}.np-tabs button{min-width:0;min-height:var(--np-best-tabs-mobile-height);padding:0 var(--np-best-tabs-mobile-padding-x);font-size:var(--np-best-tabs-mobile-font-size)}}
</style>
