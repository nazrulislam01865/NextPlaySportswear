<script setup lang="ts">
import { useId } from 'vue';

type SocialNetwork = 'youtube' | 'instagram' | 'facebook' | 'tiktok';

defineProps<{
  network: SocialNetwork;
}>();

const instagramGradientId = `np-instagram-${useId()}`;
</script>

<template>
  <span class="np-social-brand-icon" aria-hidden="true">
    <svg v-if="network === 'youtube'" viewBox="0 0 24 24" focusable="false">
      <rect x="1" y="4.5" width="22" height="15" rx="4" fill="#ff0000" />
      <path d="M10 8.7 16.1 12 10 15.3Z" fill="#ffffff" />
    </svg>

    <svg v-else-if="network === 'instagram'" viewBox="0 0 24 24" focusable="false">
      <defs>
        <linearGradient :id="instagramGradientId" x1="3" y1="21" x2="21" y2="3" gradientUnits="userSpaceOnUse">
          <stop offset="0" stop-color="#ffb000" />
          <stop offset="0.38" stop-color="#ff2d55" />
          <stop offset="0.72" stop-color="#d62976" />
          <stop offset="1" stop-color="#6a45d8" />
        </linearGradient>
      </defs>
      <rect x="3.1" y="3.1" width="17.8" height="17.8" rx="5.3" fill="none" :stroke="`url(#${instagramGradientId})`" stroke-width="2.2" />
      <circle cx="12" cy="12" r="4.1" fill="none" :stroke="`url(#${instagramGradientId})`" stroke-width="2.1" />
      <circle cx="17.4" cy="6.7" r="1.15" :fill="`url(#${instagramGradientId})`" />
    </svg>

    <svg v-else-if="network === 'facebook'" viewBox="0 0 24 24" focusable="false">
      <circle cx="12" cy="12" r="11" fill="#1877f2" />
      <path fill="#ffffff" d="M13.45 20v-7h2.48l.38-2.87h-2.86V8.3c0-.83.23-1.4 1.43-1.4h1.53V4.33a20.4 20.4 0 0 0-2.23-.12c-2.2 0-3.72 1.35-3.72 3.82v2.1H8v2.87h2.46v7h2.99Z" />
    </svg>

    <svg v-else-if="network === 'tiktok'" viewBox="0 0 24 24" focusable="false">
      <path fill="#25f4ee" d="M14.12 3.2c.22 1.87 1.28 3.32 3.1 4.11.73.32 1.47.47 2.2.49v2.93a8.28 8.28 0 0 1-2.62-.42 7.27 7.27 0 0 1-2.56-1.47v6.59c0 3.53-2.8 6.2-6.34 6.2a6.22 6.22 0 0 1-2.87-.7 6.14 6.14 0 0 1-3.61-5.52c0-3.53 2.8-6.2 6.33-6.2.31 0 .62.03.92.08v3.14a3.18 3.18 0 0 0-.92-.14 3.15 3.15 0 0 0-3.18 3.12c0 1.12.58 2.11 1.46 2.67.5.31 1.09.49 1.72.49 1.75 0 3.08-1.36 3.08-3.14V3.2h3.29Z" transform="translate(-.55 .4)" />
      <path fill="#fe2c55" d="M14.12 3.2c.22 1.87 1.28 3.32 3.1 4.11.73.32 1.47.47 2.2.49v2.93a8.28 8.28 0 0 1-2.62-.42 7.27 7.27 0 0 1-2.56-1.47v6.59c0 3.53-2.8 6.2-6.34 6.2a6.22 6.22 0 0 1-2.87-.7 6.14 6.14 0 0 1-3.61-5.52c0-3.53 2.8-6.2 6.33-6.2.31 0 .62.03.92.08v3.14a3.18 3.18 0 0 0-.92-.14 3.15 3.15 0 0 0-3.18 3.12c0 1.12.58 2.11 1.46 2.67.5.31 1.09.49 1.72.49 1.75 0 3.08-1.36 3.08-3.14V3.2h3.29Z" transform="translate(.55 -.2)" />
      <path fill="#111111" d="M14.12 3.2c.22 1.87 1.28 3.32 3.1 4.11.73.32 1.47.47 2.2.49v2.93a8.28 8.28 0 0 1-2.62-.42 7.27 7.27 0 0 1-2.56-1.47v6.59c0 3.53-2.8 6.2-6.34 6.2a6.22 6.22 0 0 1-2.87-.7 6.14 6.14 0 0 1-3.61-5.52c0-3.53 2.8-6.2 6.33-6.2.31 0 .62.03.92.08v3.14a3.18 3.18 0 0 0-.92-.14 3.15 3.15 0 0 0-3.18 3.12c0 1.12.58 2.11 1.46 2.67.5.31 1.09.49 1.72.49 1.75 0 3.08-1.36 3.08-3.14V3.2h3.29Z" />
    </svg>
  </span>
</template>

<style scoped>
.np-social-brand-icon {
  display: inline-flex;
  width: var(--np-social-brand-icon-size);
  height: var(--np-social-brand-icon-size);
  flex: 0 0 var(--np-social-brand-icon-size);
  align-items: center;
  justify-content: center;
}

.np-social-brand-icon svg {
  display: block;
  width: 100%;
  height: 100%;
}
</style>
