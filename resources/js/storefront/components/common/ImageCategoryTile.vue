<script setup lang="ts">
withDefaults(defineProps<{
  image?: string;
  alt?: string;
  title: string;
  href?: string;
  tall?: boolean;
  variant?: 'default' | 'audience' | 'category-showcase';
  actionLabel?: string;
}>(), {
  image: '',
  alt: '',
  href: '#',
  tall: false,
  variant: 'default',
  actionLabel: 'Shop Now',
});
</script>

<template>
  <a
    class="np-image-tile"
    :class="{
      'np-image-tile--tall': tall,
      'np-image-tile--audience': variant === 'audience',
      'np-image-tile--category-showcase': variant === 'category-showcase',
    }"
    :href="href"
  >
    <img :src="image || '/images/category-placeholder.svg'" :alt="alt || title" loading="lazy" />
    <span class="np-image-tile__overlay"></span>
    <strong>{{ title }}</strong>
    <span
      v-if="(variant === 'audience' || variant === 'category-showcase') && actionLabel"
      class="np-image-tile__action"
      aria-hidden="true"
    >
      <span>{{ actionLabel }}</span>
      <span class="np-image-tile__action-arrow">→</span>
    </span>
  </a>
</template>

<style scoped>
.np-image-tile {
  position: relative;
  display: block;
  min-height: 280px;
  overflow: hidden;
  background: var(--np-color-surface-muted);
}

.np-image-tile--tall { min-height: 340px; }

.np-image-tile img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--np-transition-base);
}

.np-image-tile__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, transparent 46%, rgba(4, 18, 37, .75) 100%);
}

.np-image-tile strong {
  position: absolute;
  z-index: 2;
  right: 0;
  bottom: 20px;
  left: 0;
  color: var(--np-home-on-dark);
  font-family: var(--np-font-display);
  font-size: var(--np-home-tile-title-size);
  font-weight: var(--np-home-tile-title-weight);
  line-height: var(--np-home-tile-title-line-height);
  text-align: center;
  text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
  text-transform: uppercase;
}

.np-image-tile--audience {
  min-height: 0;
  aspect-ratio: var(--np-audience-tile-ratio);
}
.np-image-tile--audience img {
  transition: transform var(--np-audience-hover-duration) var(--np-audience-hover-easing);
}
.np-image-tile--audience strong {
  bottom: var(--np-audience-title-bottom);
  font-size: var(--np-audience-title-size);
  font-weight: var(--np-audience-title-weight);
  letter-spacing: 0;
  transition: transform var(--np-audience-hover-duration) var(--np-audience-hover-easing);
}
.np-image-tile__action {
  position: absolute;
  z-index: 2;
  right: 0;
  bottom: var(--np-audience-action-bottom);
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--np-audience-action-gap);
  color: var(--np-home-on-dark);
  font-family: var(--np-font-body);
  font-size: var(--np-audience-action-size);
  font-weight: var(--np-audience-action-weight);
  line-height: 1;
  opacity: 0;
  pointer-events: none;
  transform: translateY(var(--np-audience-action-reveal-distance));
  transition: opacity var(--np-audience-hover-duration) var(--np-audience-hover-easing),
    transform var(--np-audience-hover-duration) var(--np-audience-hover-easing);
}
.np-image-tile__action-arrow {
  font-size: 1.25em;
  line-height: .8;
}
.np-image-tile--audience:hover img,
.np-image-tile--audience:focus-visible img {
  transform: scale(var(--np-audience-hover-scale));
}
.np-image-tile--audience:hover strong,
.np-image-tile--audience:focus-visible strong {
  transform: translateY(calc(-1 * var(--np-audience-title-hover-lift)));
}
.np-image-tile--audience:hover .np-image-tile__action,
.np-image-tile--audience:focus-visible .np-image-tile__action {
  opacity: 1;
  transform: translateY(0);
}
.np-image-tile--audience:not(:hover):not(:focus-visible) .np-image-tile__action {
  transform: translateY(var(--np-audience-action-reveal-distance));
}

.np-image-tile--category-showcase {
  min-height: 0;
  aspect-ratio: 1.04;
}
.np-image-tile--category-showcase .np-image-tile__overlay {
  background: linear-gradient(180deg, rgba(6,31,68,.02) 20%, rgba(6,31,68,.10) 48%, rgba(6,31,68,.72) 100%);
}
.np-image-tile--category-showcase img {
  transition: transform var(--np-audience-hover-duration) var(--np-audience-hover-easing);
}
.np-image-tile--category-showcase strong {
  bottom: 42px;
  font-size: var(--np-home-tile-title-size);
  font-weight: var(--np-home-tile-title-weight);
  letter-spacing: 0;
  text-shadow: none;
  transition: transform var(--np-audience-hover-duration) var(--np-audience-hover-easing);
}
.np-image-tile--category-showcase:hover img,
.np-image-tile--category-showcase:focus-visible img {
  transform: scale(var(--np-audience-hover-scale));
}
.np-image-tile--category-showcase:hover strong,
.np-image-tile--category-showcase:focus-visible strong {
  transform: translateY(calc(-1 * var(--np-audience-title-hover-lift)));
}
.np-image-tile--category-showcase:hover .np-image-tile__action,
.np-image-tile--category-showcase:focus-visible .np-image-tile__action {
  opacity: 1;
  transform: translateY(0);
}
.np-image-tile--category-showcase:not(:hover):not(:focus-visible) .np-image-tile__action {
  transform: translateY(var(--np-audience-action-reveal-distance));
}

.np-image-tile:not(.np-image-tile--audience):not(.np-image-tile--category-showcase):hover img { transform: scale(1.025); }

@media (max-width: 700px) {
  .np-image-tile--audience { aspect-ratio: 16 / 10; }
  .np-image-tile--audience strong { bottom: 22px; font-size: var(--np-home-tile-title-size-mobile); line-height: var(--np-home-tile-title-line-height-mobile); }
  .np-image-tile--audience .np-image-tile__action { bottom: 14px; font-size: var(--np-audience-action-size); }
  .np-image-tile--category-showcase { aspect-ratio: 4 / 3; }
  .np-image-tile--category-showcase strong { bottom: 24px; font-size: var(--np-home-tile-title-size-mobile); line-height: var(--np-home-tile-title-line-height-mobile); }
  .np-image-tile--category-showcase .np-image-tile__action { bottom: 14px; font-size: var(--np-audience-action-size); }
}

@media (prefers-reduced-motion: reduce) {
  .np-image-tile--audience img,
  .np-image-tile--audience strong,
  .np-image-tile--audience .np-image-tile__action,
  .np-image-tile--category-showcase img,
  .np-image-tile--category-showcase strong,
  .np-image-tile--category-showcase .np-image-tile__action {
    transition-duration: 0ms;
  }
}

@media (max-width: 640px) {
  .np-image-tile:not(.np-image-tile--audience):not(.np-image-tile--category-showcase) { min-height: 220px; }
  .np-image-tile:not(.np-image-tile--audience):not(.np-image-tile--category-showcase) strong { font-size: var(--np-home-tile-title-size-mobile); }
}
</style>
