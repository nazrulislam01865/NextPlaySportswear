<script setup lang="ts">
import { computed } from 'vue';
import { useStorefrontStore } from '../../stores/storefront.store';

const fallbackLogoSrc = '/images/storefront-vue/brand/nextplay-wordmark.png';
const storefront = useStorefrontStore();
const props = withDefaults(defineProps<{
  href?: string;
  alt?: string;
}>(), {
  href: '/',
});

const logoSrc = computed(() => storefront.site.logo || fallbackLogoSrc);
const logoAlt = computed(() => props.alt || storefront.site.logo_alt || storefront.site.name || 'NextPlay');
</script>

<template>
  <a class="np-brand-logo" :href="href" :aria-label="`${logoAlt} home`">
    <img :src="logoSrc" :alt="logoAlt" width="245" height="35" />
  </a>
</template>

<style scoped>
.np-brand-logo {
  display: inline-flex;
  align-items: center;
  flex: 0 0 auto;
}

.np-brand-logo img {
  display: block;
  width: clamp(180px, 12vw, 245px);
  height: auto;
  object-fit: contain;
}

@media (max-width: 560px) {
  .np-brand-logo img { width: 150px; }
}
</style>
