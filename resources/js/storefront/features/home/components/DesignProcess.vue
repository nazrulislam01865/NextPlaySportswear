<script setup lang="ts">
import ProcessStepCard from './ProcessStepCard.vue';
import type { HomeProcessStep } from '../types/home.types';
import type { StorefrontProduct } from '../../../types/product';

withDefaults(defineProps<{
  steps: HomeProcessStep[];
  previewProduct?: StorefrontProduct | null;
}>(), {
  previewProduct: null,
});
</script>

<template>
  <section class="np-process">
    <div class="np-process__inner">
      <h2>HOW TO DESIGN A T-SHIRT USING NEXTPLAY</h2>

      <div class="np-process__grid">
        <ProcessStepCard
          v-for="(step, index) in steps.slice(0, 5)"
          :key="`${index}-${step.title}`"
          :step="step"
          :index="index"
          :visual="index === 0 ? 'product' : 'placeholder'"
          :preview-product="index === 0 ? previewProduct : null"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.np-process {
  background: var(--np-color-utility-bg);
}

.np-process__inner {
  width: 100%;
  max-width: var(--np-home-process-max-width);
  margin-inline: auto;
  padding: var(--np-home-process-padding-top) var(--np-home-process-gutter) var(--np-home-process-padding-bottom);
}

.np-process h2 {
  margin: 0 0 var(--np-home-process-heading-gap);
  font-family: var(--np-home-process-heading-font-family);
  font-size: var(--np-home-process-title-size);
  font-weight: var(--np-home-process-title-weight);
  line-height: var(--np-home-process-heading-line-height);
  text-transform: uppercase;
  color: var(--np-home-text-primary);
}

.np-process__grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: var(--np-home-process-column-gap);
  align-items: start;
}

@media (max-width: 1180px) {
  .np-process__inner {
    padding-top: var(--np-home-process-padding-top-tablet);
    padding-bottom: var(--np-home-process-padding-bottom-tablet);
  }
  .np-process__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    row-gap: var(--np-home-process-row-gap-tablet);
  }
}

@media (max-width: 640px) {
  .np-process__inner {
    padding: var(--np-home-process-padding-top-mobile) var(--np-page-gutter) var(--np-home-process-padding-bottom-mobile);
  }
  .np-process h2 {
    margin-bottom: var(--np-home-process-heading-gap-mobile);
  }
  .np-process__grid {
    grid-template-columns: 1fr;
    row-gap: var(--np-home-process-row-gap-mobile);
  }
}
</style>
