<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { ArrowRight } from 'lucide-vue-next';
import AppContainer from '../../../components/ui/AppContainer.vue';
import AppSectionHeader from '../../../components/ui/AppSectionHeader.vue';
import type { StorefrontCategory } from '../../../types/category';
import type { HomeSection, HomeShopBySportSettings, HomeSportButton, HomeSportItem } from '../types/home.types';
import { sectionItems, sectionSettings, sectionTitle } from '../utils/homeSection';

interface ConfiguredSport extends StorefrontCategory {
  buttons: HomeSportButton[];
}

const props = defineProps<{ sports: StorefrontCategory[]; section?: HomeSection }>();
const activeIndex = ref(0);
const defaultSettings: HomeShopBySportSettings = {
  default_sport_id: null,
};
const settings = computed(() => sectionSettings<HomeShopBySportSettings>(props.section, defaultSettings));
const heading = computed(() => sectionTitle(props.section, 'SHOP BY SPORT'));

function configuredButtons(item: HomeSportItem): HomeSportButton[] {
  if (!Array.isArray(item.buttons)) return [];

  return item.buttons
    .map((button) => ({
      id: button?.id,
      label: String(button?.label || '').trim(),
      url: String(button?.url || '').trim(),
    }))
    .filter((button) => button.label !== '' && button.url !== '');
}

const configuredSports = computed<ConfiguredSport[]>(() => {
  const configured = sectionItems<HomeSportItem>(props.section);

  if (!configured.length) {
    return props.sports.map((sport) => ({ ...sport, buttons: [] }));
  }

  return configured.map((item) => {
    const match = props.sports.find((sport) => Number(sport.id) === Number(item.category_id));
    if (!match && !item.title && !item.image) return null;

    return {
      ...(match || {}),
      id: match?.id ?? item.category_id,
      title: item.title || match?.title || match?.short_title || 'Sport',
      short_title: item.title || match?.short_title || match?.title || 'Sport',
      banner: item.image || match?.banner || match?.image,
      image: item.image || match?.image,
      alt: item.image_alt || match?.alt || item.title || match?.title,
      url: item.url || match?.url || '/categories',
      buttons: configuredButtons(item),
    } as ConfiguredSport;
  }).filter(Boolean) as ConfiguredSport[];
});

function preferredIndex(items: ConfiguredSport[]) {
  const configuredDefault = settings.value.default_sport_id;
  if (configuredDefault) {
    const found = items.findIndex((sport) => Number(sport.id) === Number(configuredDefault));
    if (found >= 0) return found;
  }

  const baseballIndex = items.findIndex((sport) => `${sport.title || ''} ${sport.short_title || ''} ${sport.slug || ''}`.toLowerCase().includes('baseball'));
  return baseballIndex >= 0 ? baseballIndex : 0;
}

watch(configuredSports, (sports) => {
  if (!sports.length) {
    activeIndex.value = 0;
    return;
  }

  if (activeIndex.value >= sports.length || activeIndex.value === 0) {
    activeIndex.value = preferredIndex(sports);
  }
}, { immediate: true, deep: true });

const active = computed<ConfiguredSport>(() => configuredSports.value[activeIndex.value] || {
  title: 'Baseball',
  short_title: 'Baseball',
  banner: '/storage/storefront/home/baseball.webp',
  url: '/categories',
  buttons: [],
});

function previous() {
  if (!configuredSports.value.length) return;
  activeIndex.value = (activeIndex.value - 1 + configuredSports.value.length) % configuredSports.value.length;
}

function next() {
  if (!configuredSports.value.length) return;
  activeIndex.value = (activeIndex.value + 1) % configuredSports.value.length;
}
</script>

<template>
  <section class="np-sport-section np-section--compact">
    <AppContainer class="np-sport-section__head-container">
      <AppSectionHeader :title="heading" controls controls-variant="showcase" :active-index="activeIndex" @previous="previous" @next="next" />
    </AppContainer>

    <div class="np-sport-hero">
      <img :src="active.banner || active.image || '/storage/storefront/home/baseball.webp'" :alt="active.alt || active.title || 'Sport'" loading="lazy" />
      <span class="np-sport-hero__overlay"></span>

      <div class="np-sport-hero__content">
        <a :href="active.url || '/categories'" class="np-sport-hero__title">{{ active.short_title || active.title || 'BASEBALL' }}</a>

        <nav v-if="active.buttons.length" aria-label="Sport quick links">
          <a
            v-for="(button, index) in active.buttons"
            :key="button.id || `${button.label}-${index}`"
            :href="button.url"
            class="np-sport-link"
          >
            <span class="np-sport-link__label">{{ button.label }}</span>
            <ArrowRight class="np-sport-link__arrow" :stroke-width="1.7" aria-hidden="true" />
          </a>
        </nav>
      </div>
    </div>
  </section>
</template>

<style scoped>
.np-sport-section{padding-top:38px;padding-bottom:64px}.np-sport-section__head-container{max-width:min(1928px, calc(100vw - 24px));padding-inline:clamp(24px, 3vw, 60px)}.np-sport-section :deep(.np-section-head){margin-bottom:24px}.np-sport-section :deep(.np-section-title){font-size:var(--np-home-section-title-size);line-height:var(--np-type-title-3-line-height);letter-spacing:0}.np-sport-hero{position:relative;height:clamp(460px, 33vw, 680px);overflow:hidden;background:var(--np-color-navy-950)}.np-sport-hero>img{width:100%;height:100%;object-fit:cover}.np-sport-hero__overlay{position:absolute;inset:0;background:var(--np-home-overlay-sport)}.np-sport-hero__content{position:absolute;left:50%;bottom:56px;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;text-align:center;color:var(--np-home-on-dark)}.np-sport-hero__title{font-family:var(--np-home-sport-title-font-family);font-size:var(--np-home-sport-title-size);font-weight:var(--np-home-sport-title-weight);line-height:var(--np-type-title-2-line-height);text-transform:uppercase;color:var(--np-home-on-dark);letter-spacing:0}.np-sport-hero nav{display:flex;justify-content:center;gap:8px;margin-top:20px}.np-sport-link{position:relative;display:flex;width:var(--np-home-sport-link-min-width);min-width:var(--np-home-sport-link-min-width);min-height:48px;padding:15px 22px;align-items:center;justify-content:center;overflow:hidden;border:0;background:var(--np-home-sport-link-mask-bg);backdrop-filter:blur(var(--np-home-sport-link-mask-blur));-webkit-backdrop-filter:blur(var(--np-home-sport-link-mask-blur));font-family:var(--np-home-sport-link-font-family);font-size:var(--np-home-sport-link-size);font-weight:var(--np-home-sport-link-weight);line-height:1;text-transform:uppercase;text-decoration:none;color:var(--np-home-on-dark);cursor:pointer;transition:background var(--np-home-sport-link-transition) var(--np-home-sport-link-easing)}.np-sport-link__label{flex:0 1 auto;min-width:0;transition:transform var(--np-home-sport-link-transition) var(--np-home-sport-link-easing)}.np-sport-link__arrow{position:absolute;right:var(--np-home-sport-link-arrow-offset);top:50%;width:var(--np-home-sport-link-arrow-size);height:var(--np-home-sport-link-arrow-size);opacity:0;pointer-events:none;transform:translate(4px,-50%);transition:opacity var(--np-home-sport-link-transition) var(--np-home-sport-link-easing),transform var(--np-home-sport-link-transition) var(--np-home-sport-link-easing)}.np-sport-link:hover,.np-sport-link:focus-visible{background:var(--np-home-sport-link-mask-bg-hover)}.np-sport-link:hover .np-sport-link__label,.np-sport-link:focus-visible .np-sport-link__label{transform:translateX(calc(-1 * var(--np-home-sport-link-label-hover-shift)))}.np-sport-link:hover .np-sport-link__arrow,.np-sport-link:focus-visible .np-sport-link__arrow{opacity:1;transform:translate(0,-50%)}@media(max-width:900px){.np-sport-section{padding-top:24px;padding-bottom:40px}.np-sport-section__head-container{max-width:100%;padding-inline:16px}.np-sport-section :deep(.np-section-head){margin-bottom:18px}.np-sport-hero{height:430px}.np-sport-hero__content{width:92%;bottom:34px}.np-sport-hero nav{flex-wrap:wrap;gap:6px;margin-top:14px}.np-sport-link{width:auto;min-width:0;min-height:42px;padding:11px 18px;font-size:var(--np-home-sport-link-size-mobile)}}
</style>
