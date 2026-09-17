<script setup lang="ts">
import { computed } from 'vue';
import { Heart, MousePointer2 } from 'lucide-vue-next';
import type { HomeProcessStep } from '../types/home.types';
import type { StorefrontProduct } from '../../../types/product';

const props = withDefaults(defineProps<{
  step: HomeProcessStep;
  index: number;
  visual?: 'product' | 'placeholder';
  previewProduct?: StorefrontProduct | null;
}>(), {
  visual: 'placeholder',
  previewProduct: null,
});

const stepNumber = computed(() => String(props.index + 1).padStart(2, '0'));
const previewImage = computed(() => props.previewProduct?.image || '/images/storefront-vue/design-process.png');
const previewTitle = computed(() => props.previewProduct?.short_title || props.previewProduct?.title || 'Custom Teamwear Product');
const previewMeta = computed(() => {
  const values = [props.previewProduct?.category, props.previewProduct?.subcategory].filter(Boolean);
  return values.length ? values.join(' · ') : 'Jersey · Basketball';
});
</script>

<template>
  <article class="np-process-step">
    <span class="np-process-step__number">{{ stepNumber }}</span>

    <div class="np-process-step__copy">
      <h3>{{ step.title }}</h3>
      <p>{{ step.description }}</p>
    </div>

    <div class="np-process-step__visual">
      <template v-if="visual === 'product'">
        <div class="np-process-step__preview-card" aria-hidden="true">
          <div class="np-process-step__preview-media">
            <img :src="previewImage" :alt="previewTitle" loading="lazy" />
            <span class="np-process-step__preview-wish"><Heart :size="10" :stroke-width="1.6" /></span>
          </div>
          <div class="np-process-step__preview-body">
            <span>{{ previewMeta }}</span>
            <strong>{{ previewTitle }}</strong>
          </div>
          <span class="np-process-step__preview-action">CUSTOMIZE</span>
        </div>
        <MousePointer2 class="np-process-step__cursor" :size="36" :stroke-width="1.4" fill="currentColor" aria-hidden="true" />
      </template>
      <span v-else class="np-process-step__placeholder">Image</span>
    </div>
  </article>
</template>

<style scoped>
.np-process-step {
  min-width: 0;
  color: var(--np-home-text-primary);
}

.np-process-step__number {
  display: block;
  margin-bottom: var(--np-home-process-number-gap);
  font-family: var(--np-home-process-step-number-font-family);
  font-size: var(--np-home-process-step-number-size);
  font-weight: var(--np-home-process-step-number-weight);
  line-height: 1;
  color: var(--np-home-text-primary);
}

.np-process-step__copy {
  min-height: var(--np-home-process-copy-min-height);
}

.np-process-step h3 {
  margin: 0 0 var(--np-home-process-step-title-gap);
  font-family: var(--np-home-process-step-title-font-family);
  font-size: var(--np-home-process-step-title-size);
  font-weight: var(--np-home-process-step-title-weight);
  line-height: 1.2;
  color: var(--np-home-text-primary);
}

.np-process-step p {
  margin: 0;
  font-family: var(--np-home-process-step-description-font-family);
  font-size: var(--np-home-process-step-description-size);
  font-weight: var(--np-home-process-step-description-weight);
  line-height: var(--np-home-process-step-description-line-height);
  color: var(--np-home-process-step-description-color);
}

.np-process-step__visual {
  position: relative;
  display: grid;
  height: var(--np-home-process-image-height);
  margin-top: var(--np-home-process-image-gap);
  overflow: hidden;
  place-items: center;
  background: var(--np-color-white);
}

.np-process-step__placeholder {
  font-family: var(--np-font-body);
  font-size: var(--np-home-process-placeholder-size);
  font-weight: var(--np-weight-regular);
  color: var(--np-color-text-primary);
}

.np-process-step__preview-card {
  width: var(--np-home-process-preview-width);
  overflow: hidden;
  border: 1px solid var(--np-product-card-border);
  background: var(--np-color-white);
  box-shadow: var(--np-shadow-xs);
}

.np-process-step__preview-media {
  position: relative;
  height: var(--np-home-process-preview-media-height);
  overflow: hidden;
  background: var(--np-home-card-media-bg);
}

.np-process-step__preview-media img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  object-position: center top;
}

.np-process-step__preview-wish {
  position: absolute;
  top: 5px;
  right: 5px;
  display: grid;
  width: 14px;
  height: 14px;
  place-items: center;
  background: var(--np-color-white);
  color: var(--np-color-navy-950);
}

.np-process-step__preview-body {
  display: grid;
  gap: 3px;
  min-height: 44px;
  padding: 7px 8px 6px;
  font-family: var(--np-font-body);
}

.np-process-step__preview-body span {
  overflow: hidden;
  color: var(--np-product-card-meta);
  font-size: 5px;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.np-process-step__preview-body strong {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  color: var(--np-product-card-title);
  font-size: 6px;
  font-weight: var(--np-weight-medium);
  line-height: 1.3;
}

.np-process-step__preview-action {
  display: grid;
  min-height: 15px;
  place-items: center;
  background: var(--np-color-orange);
  color: var(--np-color-white);
  font-family: var(--np-font-display);
  font-size: 5px;
  font-weight: var(--np-weight-semibold);
  line-height: 1;
}

.np-process-step__cursor {
  position: absolute;
  z-index: 2;
  left: calc(50% + var(--np-home-process-cursor-offset-x));
  top: var(--np-home-process-cursor-offset-y);
  color: var(--np-color-navy-950);
  filter: drop-shadow(0 1px 0 var(--np-color-white));
}

@media (max-width: 1180px) {
  .np-process-step__visual {
    height: var(--np-home-process-image-height-tablet);
  }
}

@media (max-width: 640px) {
  .np-process-step__copy {
    min-height: 0;
  }
  .np-process-step__visual {
    height: var(--np-home-process-image-height-mobile);
  }
}
</style>
