<script setup lang="ts">
const logoSrc = '/images/storefront-vue/brand/nextplay-wordmark.png';

withDefaults(defineProps<{
  href?: string;
  alt?: string;
}>(), {
  href: '/',
  alt: 'NextPlay',
});
</script>

<template>
  <a class="np-brand-logo" :href="href" :aria-label="`${alt} home`">
    <img :src="logoSrc" :alt="alt" width="245" height="35" />
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
}

@media (max-width: 560px) {
  .np-brand-logo img { width: 150px; }
}
</style>
