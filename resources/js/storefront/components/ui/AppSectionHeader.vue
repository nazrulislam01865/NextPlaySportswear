<script setup lang="ts">
import CarouselControls from '../common/CarouselControls.vue';

withDefaults(defineProps<{
  title: string;
  linkLabel?: string;
  linkHref?: string;
  controls?: boolean;
  controlsVariant?: 'default' | 'compact' | 'showcase';
  activeIndex?: number;
  previousDisabled?: boolean;
  nextDisabled?: boolean;
}>(), {
  linkLabel: '',
  linkHref: '',
  controls: false,
  controlsVariant: 'default',
  activeIndex: 0,
  previousDisabled: false,
  nextDisabled: false,
});

const emit = defineEmits<{ previous: []; next: [] }>();
</script>

<template>
  <div class="np-section-head">
    <div class="np-section-head__left">
      <h2 class="np-section-title">{{ title }}</h2>
      <slot name="tabs" />
    </div>
    <div class="np-section-head__right">
      <a v-if="linkLabel && linkHref" class="np-section-head__link" :href="linkHref">{{ linkLabel }}</a>
      <CarouselControls
        v-if="controls"
        :variant="controlsVariant"
        :active-index="activeIndex"
        :previous-disabled="previousDisabled"
        :next-disabled="nextDisabled"
        @previous="emit('previous')"
        @next="emit('next')"
      />
    </div>
  </div>
</template>

<style scoped>
.np-section-head { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:18px; }
.np-section-head__left { display:flex; align-items:flex-end; gap:16px; min-width:0; }
.np-section-head__right { display:flex; align-items:center; gap:12px; }
.np-section-head__link { font-size:10px; color:var(--np-color-navy-950); text-decoration:underline; text-underline-offset:3px; }
@media (max-width: 640px) { .np-section-head { align-items:center; } .np-section-head__left { align-items:flex-start; flex-direction:column; gap:8px; } }
</style>
