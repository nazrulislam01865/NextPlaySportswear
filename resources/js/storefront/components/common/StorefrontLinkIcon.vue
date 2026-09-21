<script setup lang="ts">
import { computed } from 'vue';
import SocialBrandIcon from './SocialBrandIcon.vue';
import StorefrontIcon from './StorefrontIcon.vue';

const props = withDefaults(defineProps<{
  source?: string | null;
  size?: number;
  variant?: 'storefront' | 'social';
}>(), {
  source: null,
  size: 16,
  variant: 'storefront',
});

const isImageSource = computed(() => {
  const source = props.source?.trim() || '';
  if (!source) return false;

  return source.startsWith('/storage/')
    || source.startsWith('data:image/')
    || /^https?:\/\//i.test(source)
    || /\.(png|jpe?g|webp)(\?.*)?$/i.test(source);
});

const imageStyle = computed(() => props.variant === 'social'
  ? undefined
  : { width: `${props.size}px`, height: `${props.size}px` });
</script>

<template>
  <img
    v-if="source && isImageSource"
    :src="source"
    alt=""
    aria-hidden="true"
    class="np-storefront-link-icon"
    :class="{ 'np-storefront-link-icon--social': variant === 'social' }"
    :style="imageStyle"
  >
  <SocialBrandIcon v-else-if="source && variant === 'social'" :network="source" />
  <StorefrontIcon v-else-if="source" :name="source" :size="size" />
</template>

<style scoped>
.np-storefront-link-icon {
  display: block;
  flex: 0 0 auto;
  object-fit: contain;
}

.np-storefront-link-icon--social {
  width: var(--np-social-brand-icon-size);
  height: var(--np-social-brand-icon-size);
}
</style>
