<script setup lang="ts">
import { shallowRef, computed, ref } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay } from 'swiper/modules';
import AppButton from '../../../components/ui/AppButton.vue';
import CarouselControls from '../../../components/common/CarouselControls.vue';
import type { HomeSlide, HomeSection } from '../types/home.types';

const props = defineProps<{ slides: HomeSlide[]; section?: HomeSection }>();
const swiper = shallowRef<any>(null);
const activeIndex = ref(0);
function setSwiper(instance:any){ swiper.value=instance; activeIndex.value=instance.realIndex ?? 0; }
function setActiveIndex(instance:any){ activeIndex.value=instance.realIndex ?? 0; }

const effectiveSlides = computed<HomeSlide[]>(() => {
  if (props.slides?.length) return props.slides;
  return [{
    title: props.section?.title || 'YOUR TEAM YOUR KITS',
    description: props.section?.description || 'Performance sportswear for players, teams and clubs — from training essentials to match-day kits.',
    image: props.section?.image || 'https://i.pinimg.com/originals/8c/a5/a0/8ca5a08d215145b191f8a3eda395c50b.jpg',
    primary_label: props.section?.primary_label || 'SHOP PRODUCTS',
    primary_url: props.section?.primary_url || '/products',
    secondary_label: props.section?.secondary_label || 'CUSTOMIZE YOUR GEAR',
    secondary_url: props.section?.secondary_url || '/products',
  }];
});
function imagePosition(position?: string){ return (position || 'center').replace('-', ' '); }
</script>

<template>
  <section class="np-hero" aria-label="Featured promotions">
    <Swiper :modules="[Autoplay]" :slides-per-view="1" :loop="effectiveSlides.length > 1" :allow-touch-move="false" :simulate-touch="false" :autoplay="{ delay: 7000, disableOnInteraction: false }" @swiper="setSwiper" @slide-change="setActiveIndex">
      <SwiperSlide v-for="(slide,index) in effectiveSlides" :key="slide.id ?? index">
        <article class="np-hero__slide">
          <picture>
            <source v-if="slide.mobile_image" media="(max-width:640px)" :srcset="slide.mobile_image"/>
            <img :src="slide.image || 'https://i.pinimg.com/originals/8c/a5/a0/8ca5a08d215145b191f8a3eda395c50b.jpg'" :alt="slide.alt || slide.title || 'NextPlay sportswear'" :style="{ objectPosition: imagePosition(slide.image_focal_position) }" />
          </picture>
          <div class="np-hero__shade"></div>
          <div v-if="slide.show_content !== false" class="np-hero__content">
            <p v-if="slide.show_eyebrow && slide.eyebrow" class="np-hero__eyebrow">{{ slide.eyebrow }}</p>
            <h1 v-if="slide.show_title !== false" class="np-hero__title">{{ slide.title || 'YOUR TEAM YOUR KITS' }}</h1>
            <p v-if="slide.show_description !== false" class="np-hero__copy">{{ slide.description || 'Performance sportswear for players, teams and clubs — from training essentials to match-day kits.' }}</p>
            <div class="np-hero__actions">
              <AppButton v-if="slide.show_primary_button !== false" :href="slide.primary_url || '/products'" :target="slide.primary_target || '_self'" size="hero" hover-effect="chevrons">{{ slide.primary_label || 'SHOP PRODUCTS' }}</AppButton>
              <AppButton v-if="slide.show_secondary_button !== false" :href="slide.secondary_url || '/products'" :target="slide.secondary_target || '_self'" variant="outline-light" size="hero" hover-effect="chevrons">{{ slide.secondary_label || 'CUSTOMIZE YOUR GEAR' }}</AppButton>
            </div>
          </div>
        </article>
      </SwiperSlide>
    </Swiper>
    <div v-if="effectiveSlides.length > 1" class="np-hero__controls"><CarouselControls variant="hero" :active-index="activeIndex" @previous="swiper?.slidePrev()" @next="swiper?.slideNext()"/></div>
  </section>
</template>

<style scoped>
.np-hero {
  position: relative;
  width: 100%;
  height: var(--np-hero-height);
  background: var(--np-color-navy-950);
  overflow: hidden;
}
.np-hero :deep(.swiper),
.np-hero :deep(.swiper-wrapper),
.np-hero :deep(.swiper-slide),
.np-hero__slide { height: 100%; }
.np-hero__slide { position: relative; }
.np-hero__slide picture,
.np-hero__slide img { display: block; width: 100%; height: 100%; }
.np-hero__slide img { object-fit: cover; }
.np-hero__shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, var(--np-color-hero-overlay-strong) 0%, var(--np-color-hero-overlay-mid) 42%, var(--np-color-hero-overlay-soft) 68%, var(--np-color-hero-overlay-soft) 100%);
}
.np-hero__content {
  position: absolute;
  z-index: 2;
  left: max(var(--np-first-fold-gutter), calc((100vw - var(--np-first-fold-max)) / 2 + var(--np-first-fold-gutter)));
  bottom: clamp(58px, 6.2vw, 92px);
  width: min(650px, calc(100% - (var(--np-first-fold-gutter) * 2)));
  color: var(--np-color-white);
}
.np-hero__eyebrow {
  margin: 0 0 12px;
  font-family: var(--np-home-hero-eyebrow-font-family);
  font-size: var(--np-home-hero-eyebrow-size);
  font-weight: var(--np-home-hero-eyebrow-weight);
  letter-spacing: 0;
  line-height: var(--np-type-tag-line-height);
  text-transform: uppercase;
}
.np-hero__title {
  margin: 0;
  font-family: var(--np-home-hero-title-font-family);
  font-size: var(--np-home-hero-title-size);
  font-weight: var(--np-home-hero-title-weight);
  line-height: var(--np-type-title-1-line-height);
  letter-spacing: 0;
  text-transform: uppercase;
}
.np-hero__copy {
  max-width: 620px;
  margin: 20px 0 0;
  font-family: var(--np-home-hero-copy-font-family);
  font-size: var(--np-home-hero-copy-size);
  font-weight: var(--np-home-hero-copy-weight);
  line-height: var(--np-type-body-1-line-height);
  color: var(--np-home-on-dark);
}
.np-hero__actions { display: flex; gap: 10px; margin-top: 32px; }
.np-hero__controls {
  position: absolute;
  z-index: 3;
  right: max(var(--np-first-fold-gutter), calc((100vw - var(--np-first-fold-max)) / 2 + var(--np-first-fold-gutter)));
  bottom: clamp(58px, 6.2vw, 92px);
}


@media (max-width: 700px) {
  .np-hero { height: 560px; }
  .np-hero__content { left: 20px; right: 20px; bottom: 58px; width: auto; }
  .np-hero__title { font-size: var(--np-home-hero-title-size-mobile); }
  .np-hero__copy { font-size: var(--np-home-hero-copy-size-mobile); }
  .np-hero__actions { flex-wrap: wrap; margin-top: 24px; }
  .np-hero__controls { right: 20px; bottom: 18px; }
}
</style>
