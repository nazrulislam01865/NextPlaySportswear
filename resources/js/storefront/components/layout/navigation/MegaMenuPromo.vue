<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';
import type { NavigationItem } from '../../../types/navigation';
import type { MegaMenuConfig } from './mega-menu.config';
import { resolveNavigationUrl } from './mega-menu.config';

const props = defineProps<{
  promo: MegaMenuConfig['promo'];
  navigation?: NavigationItem[];
}>();

const promoHref = () => resolveNavigationUrl(
  props.navigation,
  props.promo.href,
  props.promo.lookupLabels ?? [props.promo.label],
);
</script>

<template>
  <aside class="np-mega-promo">
    <a class="np-mega-promo__image" :href="promoHref()" :aria-label="promo.label">
      <img :src="promo.image" :alt="promo.alt" loading="eager" />
    </a>
    <a class="np-mega-promo__cta" :href="promoHref()">
      <span>{{ promo.label }}</span>
      <ArrowRight :size="18" :stroke-width="1.6" aria-hidden="true" />
    </a>
  </aside>
</template>

<style scoped>
.np-mega-promo {
  height: var(--np-mega-menu-height);
  border-left: 1px solid var(--np-mega-menu-border);
  background: var(--np-mega-menu-bg);
}

.np-mega-promo__image {
  display: block;
  width: 100%;
  height: calc(var(--np-mega-menu-height) - var(--np-mega-menu-promo-cta-height));
  overflow: hidden;
}

.np-mega-promo__image img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 48%;
}

.np-mega-promo__cta {
  display: flex;
  height: var(--np-mega-menu-promo-cta-height);
  align-items: center;
  gap: var(--np-space-4);
  padding-inline: var(--np-mega-menu-promo-cta-padding);
  border-top: 1px solid var(--np-mega-menu-border);
  color: var(--np-color-orange);
  font-family: var(--np-font-body);
  font-size: var(--np-mega-menu-promo-size);
  font-weight: var(--np-weight-semibold);
}

.np-mega-promo__cta :deep(svg) {
  transition: transform var(--np-transition-fast);
}

.np-mega-promo__cta:hover :deep(svg),
.np-mega-promo__cta:focus-visible :deep(svg) {
  transform: translateX(var(--np-space-1));
}
</style>
