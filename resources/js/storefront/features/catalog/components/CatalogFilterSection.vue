<script setup lang="ts">
withDefaults(defineProps<{
  title: string;
  count?: number | null;
  expanded: boolean;
  disabled?: boolean;
}>(), {
  count: null,
  disabled: false,
});

const emit = defineEmits<{ toggle: [] }>();
</script>

<template>
  <section class="np-catalog-filter-section">
    <button
      type="button"
      class="np-catalog-filter-section__trigger"
      :aria-expanded="expanded"
      :disabled="disabled"
      @click="emit('toggle')"
    >
      <span class="np-catalog-filter-section__label">
        {{ title }}
        <span v-if="count !== null" class="np-catalog-filter-section__count">({{ count }})</span>
      </span>
      <span class="np-catalog-filter__toggle" aria-hidden="true">{{ expanded ? '−' : '+' }}</span>
    </button>
    <div v-if="expanded" class="np-catalog-filter-section__body">
      <slot />
    </div>
  </section>
</template>

<style scoped>
.np-catalog-filter-section {
  border-bottom: 1px solid var(--np-men-filter-border);
}

.np-catalog-filter-section__trigger {
  display: flex;
  width: 100%;
  min-height: var(--np-men-filter-row-height);
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--np-color-navy-950);
  cursor: pointer;
  text-align: left;
}

.np-catalog-filter-section__trigger:disabled {
  cursor: default;
  opacity: .55;
}

.np-catalog-filter-section__label {
  font-family: var(--np-font-display);
  font-size: var(--np-men-filter-section-size);
  font-weight: var(--np-men-filter-section-weight);
  line-height: 1.15;
  text-transform: uppercase;
  transition: color var(--np-transition-base);
}

.np-catalog-filter-section__trigger:hover .np-catalog-filter-section__label,
.np-catalog-filter-section__trigger:focus-visible .np-catalog-filter-section__label {
  color: var(--np-color-orange);
}

.np-catalog-filter-section__count {
  color: var(--np-men-filter-count-color);
  font-family: var(--np-font-body);
  font-size: var(--np-men-filter-count-size);
  font-weight: var(--np-men-filter-count-weight);
}

.np-catalog-filter__toggle {
  flex: 0 0 auto;
  color: var(--np-color-navy-950);
  font-family: var(--np-font-body);
  font-size: var(--np-men-filter-toggle-size);
  font-weight: 300;
  line-height: 1;
  transition: color var(--np-transition-base);
}

.np-catalog-filter-section__trigger:hover .np-catalog-filter__toggle,
.np-catalog-filter-section__trigger:focus-visible .np-catalog-filter__toggle {
  color: var(--np-color-orange);
}

.np-catalog-filter-section__body {
  padding: 0 0 var(--np-men-filter-body-padding-bottom);
}
</style>
