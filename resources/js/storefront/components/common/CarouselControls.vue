<script setup lang="ts">
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
  variant?: 'default' | 'hero' | 'compact' | 'showcase';
  activeIndex?: number;
  previousDisabled?: boolean;
  nextDisabled?: boolean;
}>(), {
  variant: 'default',
  activeIndex: 0,
  previousDisabled: false,
  nextDisabled: false,
});

const emit = defineEmits<{ previous: []; next: [] }>();

const markerCount = computed(() => props.variant === 'hero' ? 3 : 2);
const activeMarkerIndex = computed(() => {
  const count = markerCount.value;
  return ((props.activeIndex % count) + count) % count;
});
</script>

<template>
  <div class="np-carousel-controls" :class="`np-carousel-controls--${variant}`" aria-label="Carousel controls">
    <button type="button" aria-label="Previous" :disabled="previousDisabled" @click="emit('previous')">
      <ArrowLeft :size="variant === 'compact' ? 12 : 14" :stroke-width="1.6" />
    </button>

    <span class="np-carousel-controls__indicator" aria-hidden="true">
      <span
        v-if="variant === 'hero' || variant === 'showcase'"
        :class="`np-carousel-controls__${variant}-markers`"
      >
        <span
          v-for="markerIndex in markerCount"
          :key="markerIndex"
          class="np-carousel-controls__marker"
          :class="[
            `np-carousel-controls__marker--${variant}`,
            { 'np-carousel-controls__marker--active': markerIndex - 1 === activeMarkerIndex },
          ]"
        ></span>
      </span>
    </span>

    <button type="button" aria-label="Next" :disabled="nextDisabled" @click="emit('next')">
      <ArrowRight :size="variant === 'compact' ? 12 : 14" :stroke-width="1.6" />
    </button>
  </div>
</template>

<style scoped>
.np-carousel-controls { display:grid; grid-template-columns:32px 22px 32px; align-items:center; }
.np-carousel-controls button { display:grid; width:32px; height:32px; place-items:center; border:1px solid var(--np-color-border); background:#f8fafc; color:var(--np-color-navy-950); cursor:pointer; transition:background var(--np-transition-fast), color var(--np-transition-fast), border-color var(--np-transition-fast); }
.np-carousel-controls__indicator { display:block; height:1px; background:#cfd7df; }
.np-carousel-controls button:hover:not(:disabled) { background:var(--np-color-navy-950); color:#fff; }
.np-carousel-controls button:disabled { cursor:default; opacity:.45; }

.np-carousel-controls--compact { grid-template-columns:28px 20px 28px; }
.np-carousel-controls--compact button { width:28px; height:28px; background:#fff; }
.np-carousel-controls--compact .np-carousel-controls__indicator { background:#d9e0e6; }

.np-carousel-controls--showcase { grid-template-columns:var(--np-home-showcase-control-width) var(--np-home-showcase-control-track-width) var(--np-home-showcase-control-width); }
.np-carousel-controls--showcase button { width:var(--np-home-showcase-control-width); height:var(--np-home-showcase-control-height); border-color:#e6ebf0; background:#f7f9fb; }
.np-carousel-controls--showcase button svg { width:var(--np-home-showcase-control-icon-size); height:var(--np-home-showcase-control-icon-size); }
.np-carousel-controls--showcase .np-carousel-controls__indicator {
  display:flex;
  height:auto;
  align-items:center;
  justify-content:center;
  background:transparent;
}
.np-carousel-controls__showcase-markers {
  display:flex;
  align-items:center;
  justify-content:center;
  gap:var(--np-home-showcase-indicator-gap);
}
.np-carousel-controls__marker--showcase {
  display:block;
  width:var(--np-home-showcase-indicator-dot-size);
  height:var(--np-home-showcase-indicator-dot-size);
  border-radius:50%;
  background:var(--np-color-navy-950);
  transition:width var(--np-transition-fast), height var(--np-transition-fast), border-radius var(--np-transition-fast);
}
.np-carousel-controls__marker--showcase.np-carousel-controls__marker--active {
  width:var(--np-home-showcase-indicator-dash-width);
  height:var(--np-home-showcase-indicator-dash-height);
  border-radius:0;
}
.np-carousel-controls--showcase button:hover:not(:disabled) { background:var(--np-color-navy-950); color:#fff; border-color:var(--np-color-navy-950); }

.np-carousel-controls--hero { grid-template-columns:var(--np-home-hero-control-size) var(--np-home-hero-control-track-width) var(--np-home-hero-control-size); }
.np-carousel-controls--hero button { width:var(--np-home-hero-control-size); height:var(--np-home-hero-control-size); border-color:rgba(255,255,255,.78); background:rgba(6,31,68,.30); color:var(--np-color-white); }
.np-carousel-controls--hero button svg { width:var(--np-home-hero-control-icon-size); height:var(--np-home-hero-control-icon-size); }
.np-carousel-controls--hero .np-carousel-controls__indicator {
  display:flex;
  height:auto;
  margin-inline:10px;
  align-items:center;
  justify-content:center;
  background:transparent;
}
.np-carousel-controls__hero-markers {
  display:flex;
  align-items:center;
  justify-content:center;
  gap:var(--np-home-hero-indicator-gap);
}
.np-carousel-controls__marker--hero {
  display:block;
  width:var(--np-home-hero-indicator-dot-size);
  height:var(--np-home-hero-indicator-dot-size);
  border-radius:50%;
  background:var(--np-color-white);
  transition:width var(--np-transition-fast), height var(--np-transition-fast), border-radius var(--np-transition-fast);
}
.np-carousel-controls__marker--hero.np-carousel-controls__marker--active {
  width:var(--np-home-hero-indicator-dash-width);
  height:var(--np-home-hero-indicator-dash-height);
  border-radius:0;
}
.np-carousel-controls--hero button:hover:not(:disabled) { background:rgba(6,31,68,.62); color:var(--np-color-white); }

@media (max-width:700px) {
  .np-carousel-controls--showcase { grid-template-columns:var(--np-home-showcase-control-width-mobile) var(--np-home-showcase-control-track-width-mobile) var(--np-home-showcase-control-width-mobile); }
  .np-carousel-controls--showcase button { width:var(--np-home-showcase-control-width-mobile); height:var(--np-home-showcase-control-height-mobile); }

  .np-carousel-controls--hero { grid-template-columns:var(--np-home-hero-control-size-mobile) var(--np-home-hero-control-track-width-mobile) var(--np-home-hero-control-size-mobile); }
  .np-carousel-controls--hero button { width:var(--np-home-hero-control-size-mobile); height:var(--np-home-hero-control-size-mobile); }
}
</style>
