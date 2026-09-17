<script setup lang="ts">
import type { NavigationItem } from '../../../types/navigation';
import type { MegaMenuConfig } from './mega-menu.config';
import { resolveNavigationUrl } from './mega-menu.config';

defineProps<{
  topChoices: MegaMenuConfig['topChoices'];
  navigation?: NavigationItem[];
}>();
</script>

<template>
  <aside class="np-mega-top-choices">
    <p>{{ topChoices.eyebrow }}</p>
    <nav aria-label="Top choices">
      <a
        v-for="link in topChoices.links"
        :key="link.label"
        :href="resolveNavigationUrl(navigation, link.href, link.lookupLabels ?? [link.label])"
      >{{ link.label }}</a>
    </nav>
  </aside>
</template>

<style scoped>
.np-mega-top-choices {
  height: var(--np-mega-menu-height);
  padding: var(--np-mega-menu-rail-top) var(--np-mega-menu-rail-padding);
  border-right: 1px solid var(--np-mega-menu-border);
  background: var(--np-mega-menu-rail-bg);
}

.np-mega-top-choices p {
  margin: 0 0 var(--np-mega-menu-rail-eyebrow-gap);
  color: var(--np-mega-menu-muted);
  font-family: var(--np-font-body);
  font-size: var(--np-mega-menu-eyebrow-size);
  font-weight: var(--np-weight-regular);
}

.np-mega-top-choices nav {
  display: flex;
  flex-direction: column;
  gap: var(--np-mega-menu-rail-link-gap);
}

.np-mega-top-choices a {
  width: fit-content;
  position: relative;
  color: var(--np-mega-menu-text);
  font-family: var(--np-font-display);
  font-size: var(--np-mega-menu-heading-size);
  font-weight: var(--np-mega-menu-heading-weight);
  line-height: 1.15;
  text-transform: uppercase;
}

.np-mega-top-choices a::after {
  position: absolute;
  right: 0;
  bottom: calc(-1 * var(--np-mega-menu-underline-offset));
  left: 0;
  height: var(--np-mega-menu-underline-height);
  background: currentColor;
  content: '';
  pointer-events: none;
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform var(--np-transition-base);
}

.np-mega-top-choices a:hover::after,
.np-mega-top-choices a:focus-visible::after {
  transform: scaleX(1);
}
</style>
