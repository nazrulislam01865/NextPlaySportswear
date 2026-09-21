<script setup lang="ts">
import { computed } from 'vue';
import type { MegaMenuColumnSetting } from './mega-menu.config';
import { isUsableNavigationLink } from './mega-menu.config';

const props = defineProps<{ column: MegaMenuColumnSetting }>();
const links = computed(() => (props.column.links || []).filter(isUsableNavigationLink));
</script>

<template>
  <section class="np-mega-column">
    <h3>{{ column.title }}</h3>
    <nav :aria-label="`${column.title} links`">
      <a v-for="(link, index) in links" :key="`${link.label}-${index}`" :href="link.url || '#'">{{ link.label }}</a>
    </nav>
  </section>
</template>

<style scoped>
.np-mega-column { min-width: 0; padding-top: var(--np-mega-menu-column-top); }
.np-mega-column h3 { margin: 0 0 var(--np-mega-menu-heading-gap); color: var(--np-mega-menu-text); font-family: var(--np-font-display); font-size: var(--np-mega-menu-heading-size); font-weight: var(--np-mega-menu-heading-weight); line-height: 1; text-transform: uppercase; }
.np-mega-column nav { display: flex; flex-direction: column; gap: var(--np-mega-menu-link-gap); }
.np-mega-column a { width: fit-content; position: relative; color: var(--np-mega-menu-text); font-family: var(--np-font-body); font-size: var(--np-mega-menu-link-size); font-weight: var(--np-mega-menu-link-weight); line-height: 1.35; }
.np-mega-column a::after { position: absolute; right: 0; bottom: calc(-1 * var(--np-mega-menu-underline-offset)); left: 0; height: var(--np-mega-menu-underline-height); background: currentColor; content: ''; pointer-events: none; transform: scaleX(0); transform-origin: left center; transition: transform var(--np-transition-base); }
.np-mega-column a:hover::after,
.np-mega-column a:focus-visible::after { transform: scaleX(1); }
</style>
