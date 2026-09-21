<script setup lang="ts">
import StorefrontLinkIcon from '../common/StorefrontLinkIcon.vue';

interface FooterLinkItem {
  label: string;
  href: string;
  icon?: string | null;
}

defineProps<{
  title: string;
  items: FooterLinkItem[];
}>();
</script>

<template>
  <section class="np-footer-column">
    <h3>{{ title }}</h3>
    <nav :aria-label="`${title} links`">
      <a v-for="(item, index) in items" :key="item.href + item.label + index" :href="item.href">
        <StorefrontLinkIcon v-if="item.icon" :source="item.icon" :size="14" />
        <span>{{ item.label }}</span>
      </a>
    </nav>
  </section>
</template>

<style scoped>
.np-footer-column {
  min-width: 0;
}

.np-footer-column h3 {
  margin: 0 0 var(--np-footer-heading-gap);
  color: var(--np-footer-heading-color);
  font-family: var(--np-font-body);
  font-size: var(--np-footer-heading-size);
  font-weight: var(--np-footer-heading-weight);
  line-height: 1.2;
}

.np-footer-column nav {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--np-footer-link-gap);
}

.np-footer-column a {
  position: relative;
  display: inline-flex;
  width: fit-content;
  align-items: center;
  gap: 6px;
  color: var(--np-footer-link-color);
  font-family: var(--np-font-body);
  font-size: var(--np-footer-link-size);
  font-weight: var(--np-footer-link-weight);
  line-height: 1.3;
  text-decoration: none;
}

.np-footer-column a::after {
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

.np-footer-column a:hover::after,
.np-footer-column a:focus-visible::after {
  transform: scaleX(1);
}
</style>
