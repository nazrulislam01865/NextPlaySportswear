<script setup lang="ts">
import { computed } from 'vue';
import StorefrontLinkIcon from '../../common/StorefrontLinkIcon.vue';
import UtilityActionLink from './UtilityActionLink.vue';
import { useStorefrontStore } from '../../../stores/storefront.store';

const storefront = useStorefrontStore();
const utilityLinks = computed(() => (storefront.header.utility_links || []).filter((link) => (
  link.enabled !== false && Boolean(link.label?.trim() && link.url?.trim())
)));
const social = computed(() => storefront.footer.social ?? {});
const socialLinks = computed(() => (social.value.links || []).filter((link) => (
  link.enabled !== false && Boolean(link.label?.trim() && link.url?.trim())
)));
</script>

<template>
  <footer class="np-mega-footer">
    <div class="np-mega-footer__inner">
      <nav class="np-mega-footer__utility" aria-label="Mega menu utility links">
        <UtilityActionLink v-for="(link, index) in utilityLinks" :key="`${link.label}-${index}`" :href="link.url || '#'">
          <template #icon><StorefrontLinkIcon :source="link.icon" :size="17" /></template>
          {{ link.label }}
        </UtilityActionLink>
      </nav>

      <div v-if="social.enabled !== false && socialLinks.length" class="np-mega-footer__social" aria-label="Follow NextPlay">
        <span>{{ social.label || 'Follow Us :' }}</span>
        <a
          v-for="(link, index) in socialLinks"
          :key="`${link.label}-${index}`"
          :href="link.url || '#'"
          target="_blank"
          rel="noopener noreferrer"
          :aria-label="link.label || 'Social link'"
        ><StorefrontLinkIcon :source="link.icon" variant="social" /></a>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.np-mega-footer { height: var(--np-mega-menu-footer-height); border-top: 1px solid var(--np-mega-menu-border); border-bottom: 1px solid var(--np-mega-menu-border); background: var(--np-mega-menu-bg); }
.np-mega-footer__inner { width: 100%; max-width: var(--np-first-fold-max); height: 100%; margin-inline: auto; padding-inline: var(--np-first-fold-gutter); display: flex; align-items: center; justify-content: space-between; gap: var(--np-space-8); }
.np-mega-footer__utility,
.np-mega-footer__social,
.np-mega-footer__social a { display: flex; align-items: center; }
.np-mega-footer__utility { gap: var(--np-mega-menu-footer-utility-gap); }
.np-mega-footer__utility :deep(.np-utility-action) { color: var(--np-mega-menu-text); font-family: var(--np-font-display); font-size: var(--np-mega-menu-footer-link-size); font-weight: var(--np-utility-font-weight); }
.np-mega-footer__social { gap: var(--np-mega-menu-social-gap); color: var(--np-mega-menu-muted); font-family: var(--np-font-body); font-size: var(--np-mega-menu-social-label-size); font-weight: var(--np-weight-medium); }
.np-mega-footer__social a { justify-content: center; }
</style>
