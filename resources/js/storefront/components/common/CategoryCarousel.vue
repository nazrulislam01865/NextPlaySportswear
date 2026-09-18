<script setup lang="ts">
import { shallowRef, ref } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import AppSectionHeader from '../ui/AppSectionHeader.vue';
import ImageCategoryTile from './ImageCategoryTile.vue';
import type { StorefrontCategory } from '../../types/category';

withDefaults(defineProps<{
  title:string;
  categories:StorefrontCategory[];
  variant?: 'default' | 'showcase';
  controlsVariant?: 'default' | 'compact' | 'showcase';
  titleMeta?: string;
  edgeAwareControls?: boolean;
  contained?: boolean;
}>(), { variant:'default', controlsVariant:'default', edgeAwareControls:false, contained:false });

const swiper=shallowRef<any>(null);
const activeIndex = ref(0);
const canSlidePrevious = ref(false);
const canSlideNext = ref(false);

function syncEdgeState(instance:any){
  canSlidePrevious.value = !instance.isBeginning;
  canSlideNext.value = !instance.isEnd;
}

function setSwiper(instance:any){
  swiper.value=instance;
  activeIndex.value=instance.activeIndex ?? 0;
  syncEdgeState(instance);
}
function setActiveIndex(instance:any){
  activeIndex.value=instance.activeIndex ?? 0;
  syncEdgeState(instance);
}
</script>

<template>
  <div class="np-category-carousel" :class="[`np-category-carousel--${variant}`, { 'np-category-carousel--contained': contained }]">
    <AppSectionHeader :title="title" controls :controls-variant="controlsVariant" :active-index="activeIndex" :previous-disabled="edgeAwareControls && !canSlidePrevious" :next-disabled="edgeAwareControls && !canSlideNext" @previous="swiper?.slidePrev()" @next="swiper?.slideNext()">
      <template v-if="titleMeta" #tabs><span class="np-category-carousel__title-meta">{{ titleMeta }}</span></template>
    </AppSectionHeader>
    <Swiper
      :space-between="variant === 'showcase' ? 10 : 4"
      :slides-per-view="contained ? 1 : 1.25"
      :breakpoints="contained ? {520:{slidesPerView:2,spaceBetween:8},900:{slidesPerView:3,spaceBetween:10},1280:{slidesPerView:4,spaceBetween:10}} : variant === 'showcase' ? {520:{slidesPerView:2.05,spaceBetween:8},900:{slidesPerView:3.05,spaceBetween:10},1280:{slidesPerView:4,spaceBetween:10}} : {520:{slidesPerView:2.15},760:{slidesPerView:3.1},1024:{slidesPerView:4}}"
      @swiper="setSwiper"
      @slide-change="setActiveIndex"
      @resize="syncEdgeState"
      @breakpoint="syncEdgeState"
    >
      <SwiperSlide v-for="category in categories" :key="category.id || category.slug">
        <ImageCategoryTile
          :image="category.banner || category.image"
          :alt="category.alt"
          :title="category.short_title || category.title || 'CATEGORY'"
          :href="category.url || '/categories'"
          :variant="variant === 'showcase' ? 'category-showcase' : 'default'"
        />
      </SwiperSlide>
    </Swiper>
  </div>
</template>

<style scoped>
.np-category-carousel :deep(.swiper){overflow:visible}
.np-category-carousel--contained :deep(.swiper){overflow:hidden}
.np-category-carousel--default :deep(.np-image-tile){min-height:300px}
.np-category-carousel--showcase :deep(.np-section-head){margin-bottom:28px}
.np-category-carousel--showcase :deep(.np-section-title){font-size:var(--np-showcase-title-size)}
@media(max-width:640px){.np-category-carousel--default :deep(.np-image-tile){min-height:240px}}
</style>
