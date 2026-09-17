<script setup lang="ts">
import { shallowRef, ref } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import AppSectionHeader from '../ui/AppSectionHeader.vue';
import ProductCard from './ProductCard.vue';
import type { StorefrontProduct } from '../../types/product';

withDefaults(defineProps<{
  title: string;
  products: StorefrontProduct[];
  forceCustomize?: boolean;
  busyProductId?: number | null;
  controls?: boolean;
  linkLabel?: string;
  linkHref?: string;
  controlsVariant?: 'default' | 'compact' | 'showcase';
  cardVariant?: 'default' | 'new-arrivals';
  showPrice?: boolean;
  showBadges?: boolean;
  slidesPerView?: number;
  spaceBetween?: number;
  breakpoints?: Record<number, { slidesPerView?: number; spaceBetween?: number }>;
}>(), {
  forceCustomize: false,
  busyProductId: null,
  controls: true,
  linkLabel: '',
  linkHref: '',
  controlsVariant: 'default',
  cardVariant: 'default',
  showPrice: true,
  showBadges: true,
  slidesPerView: 1.6,
  spaceBetween: 0,
  breakpoints: () => ({
    520: { slidesPerView: 2.2, spaceBetween: 0 },
    760: { slidesPerView: 3.2, spaceBetween: 0 },
    1024: { slidesPerView: 4.2, spaceBetween: 0 },
    1280: { slidesPerView: 5, spaceBetween: 0 },
  }),
});

const emit = defineEmits<{ add: [product: StorefrontProduct]; wishlist: [product: StorefrontProduct] }>();
const swiper = shallowRef<any>(null);
const activeIndex = ref(0);
function setSwiper(instance:any){ swiper.value=instance; activeIndex.value=instance.activeIndex ?? 0; }
function setActiveIndex(instance:any){ activeIndex.value=instance.activeIndex ?? 0; }
</script>

<template>
  <div class="np-product-carousel" :class="`np-product-carousel--${cardVariant}`">
    <AppSectionHeader
      :title="title"
      :controls="controls"
      :controls-variant="controlsVariant"
      :active-index="activeIndex"
      :link-label="linkLabel"
      :link-href="linkHref"
      @previous="swiper?.slidePrev()"
      @next="swiper?.slideNext()"
    >
      <template #tabs><slot name="tabs" /></template>
    </AppSectionHeader>
    <Swiper
      :space-between="spaceBetween"
      :slides-per-view="slidesPerView"
      :breakpoints="breakpoints"
      @swiper="setSwiper"
      @slide-change="setActiveIndex"
    >
      <SwiperSlide v-for="product in products" :key="product.id">
        <ProductCard
          :product="product"
          :busy="busyProductId === product.id"
          :force-customize="forceCustomize"
          :variant="cardVariant"
          :show-price="showPrice"
          :show-badges="showBadges"
          @add="emit('add',$event)"
          @wishlist="emit('wishlist',$event)"
        />
      </SwiperSlide>
    </Swiper>
  </div>
</template>

<style scoped>
.np-product-carousel :deep(.swiper) { overflow:visible; }
.np-product-carousel :deep(.swiper-slide) { height:auto; }
</style>
