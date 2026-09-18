<script setup lang="ts">
import type { MenFilterChip } from '../../men/types/men.types';

defineProps<{
  chips: MenFilterChip[];
  disabled?: boolean;
}>();

const emit = defineEmits<{
  remove: [chip: MenFilterChip];
  clear: [];
}>();
</script>

<template>
  <div class="np-catalog-filter-chips" :class="{ 'np-catalog-filter-chips--empty': chips.length === 0 }">
    <div class="np-catalog-filter-chips__list">
      <button
        v-for="chip in chips"
        :key="chip.id"
        type="button"
        class="np-catalog-filter-chips__chip"
        :disabled="disabled"
        :aria-label="`Remove ${chip.label} filter`"
        @click="emit('remove', chip)"
      >
        <span>{{ chip.label }}</span>
        <span class="np-catalog-filter-chips__remove" aria-hidden="true">×</span>
      </button>
    </div>

    <button
      v-if="chips.length"
      type="button"
      class="np-catalog-filter-chips__clear"
      :disabled="disabled"
      @click="emit('clear')"
    >
      Clear Filter
    </button>
  </div>
</template>

<style scoped>
.np-catalog-filter-chips {
  display: flex;
  min-height: var(--np-men-filter-toolbar-height);
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
}

.np-catalog-filter-chips__list {
  display: flex;
  min-width: 0;
  flex-wrap: wrap;
  gap: var(--np-men-filter-chip-gap);
}

.np-catalog-filter-chips__chip {
  display: inline-flex;
  min-height: var(--np-men-filter-chip-height);
  align-items: center;
  justify-content: space-between;
  gap: var(--np-men-filter-chip-content-gap);
  padding: 0 var(--np-men-filter-chip-padding-inline);
  border: 0;
  background: var(--np-men-filter-chip-bg);
  color: var(--np-color-navy-950);
  cursor: pointer;
  font-family: var(--np-font-body);
  font-size: var(--np-men-filter-chip-size);
  font-weight: var(--np-men-filter-chip-weight);
  line-height: 1;
}

.np-catalog-filter-chips__remove {
  font-size: var(--np-men-filter-chip-remove-size);
  font-weight: 300;
  line-height: 1;
}

.np-catalog-filter-chips__clear {
  flex: 0 0 auto;
  min-height: var(--np-men-filter-chip-height);
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--np-color-navy-950);
  cursor: pointer;
  font-family: var(--np-font-body);
  font-size: var(--np-men-filter-clear-size);
  font-weight: var(--np-men-filter-clear-weight);
  line-height: 1;
}

.np-catalog-filter-chips__chip:hover:not(:disabled),
.np-catalog-filter-chips__chip:focus-visible:not(:disabled),
.np-catalog-filter-chips__clear:hover:not(:disabled),
.np-catalog-filter-chips__clear:focus-visible:not(:disabled) {
  color: var(--np-color-orange);
}

.np-catalog-filter-chips button:disabled {
  cursor: default;
  opacity: .55;
}

@media (max-width: 700px) {
  .np-catalog-filter-chips {
    min-height: 0;
    flex-direction: column;
    gap: 12px;
  }
}
</style>
