<script setup lang="ts">
import { useId } from 'vue';

defineProps<{
  network: string;
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

    <svg v-else-if="network === 'linkedin'" viewBox="0 0 24 24" focusable="false">
      <rect x="2" y="2" width="20" height="20" rx="3" fill="#0a66c2" />
      <circle cx="7.2" cy="8" r="1.45" fill="#fff" />
      <path d="M5.9 10.2h2.6v7.9H5.9Zm4.15 0h2.48v1.08h.04c.35-.66 1.2-1.36 2.47-1.36 2.64 0 3.13 1.74 3.13 4v4.17h-2.59v-3.7c0-.88-.02-2.02-1.23-2.02-1.23 0-1.42.96-1.42 1.95v3.77h-2.58Z" fill="#fff" />
    </svg>

    <svg v-else-if="network === 'x'" viewBox="0 0 24 24" focusable="false">
      <circle cx="12" cy="12" r="11" fill="#111" />
      <path d="M7.1 6.8h3.15l2.53 3.39 2.97-3.39h1.32l-3.68 4.2 4.1 5.52h-3.14l-2.88-3.88-3.4 3.88H6.75l4.12-4.7Z" fill="#fff" />
    </svg>

    <svg v-else-if="network === 'pinterest'" viewBox="0 0 24 24" focusable="false">
      <circle cx="12" cy="12" r="11" fill="#e60023" />
      <path d="M12.2 5.2c-3.65 0-6.1 2.62-6.1 5.72 0 2.16 1.2 3.39 1.94 3.39.3 0 .48-.85.48-1.09 0-.29-.73-.9-.73-2.1 0-2.5 1.9-4.27 4.35-4.27 2.1 0 3.66 1.2 3.66 3.39 0 1.64-.66 4.72-2.79 4.72-.77 0-1.43-.56-1.43-1.36 0-1.17.81-2.3.81-3.51 0-2.06-2.92-1.69-2.92.8 0 .52.06 1.1.3 1.58-.43 1.84-1.31 4.58-1.31 6.47 0 .58.08 1.15.13 1.73.1.12.05.11.2.05 1.56-2.14 1.5-2.57 2.2-5.37.38.72 1.36 1.1 2.13 1.1 3.27 0 4.74-3.18 4.74-6.05 0-3.05-2.63-5.23-5.66-5.23Z" fill="#fff" />
    </svg>

    <svg v-else viewBox="0 0 24 24" focusable="false">
      <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.8" />
      <path d="M2.6 12h18.8M12 2c2.6 2.7 3.9 6 3.9 10S14.6 19.3 12 22M12 2C9.4 4.7 8.1 8 8.1 12S9.4 19.3 12 22" fill="none" stroke="currentColor" stroke-width="1.6" />
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
