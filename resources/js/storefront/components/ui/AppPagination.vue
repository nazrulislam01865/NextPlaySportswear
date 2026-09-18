<script setup lang="ts">
import { computed } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
  currentPage: number;
  lastPage: number;
  disabled?: boolean;
}>(), {
  disabled: false,
});

const emit = defineEmits<{
  change: [page: number];
}>();

const pages = computed(() => {
  const last = Math.max(1, props.lastPage);
  const current = Math.min(Math.max(1, props.currentPage), last);
  const start = Math.max(1, Math.min(current - 2, last - 4));
  const end = Math.min(last, start + 4);

  return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});

function go(page: number): void {
  if (props.disabled || page < 1 || page > props.lastPage || page === props.currentPage) return;
  emit('change', page);
}
</script>

<template>
  <nav v-if="lastPage > 1" class="np-pagination" aria-label="Product pages">
    <button
      type="button"
      class="np-pagination__button np-pagination__button--arrow"
      :disabled="disabled || currentPage <= 1"
      aria-label="Previous page"
      @click="go(currentPage - 1)"
    >
      <ChevronLeft :size="18" :stroke-width="1.6" />
    </button>

    <button
      v-for="page in pages"
      :key="page"
      type="button"
      class="np-pagination__button"
      :class="{ 'np-pagination__button--active': page === currentPage }"
      :disabled="disabled"
      :aria-current="page === currentPage ? 'page' : undefined"
      @click="go(page)"
    >
      {{ page }}
    </button>

    <button
      type="button"
      class="np-pagination__button np-pagination__button--arrow"
      :disabled="disabled || currentPage >= lastPage"
      aria-label="Next page"
      @click="go(currentPage + 1)"
    >
      <ChevronRight :size="18" :stroke-width="1.6" />
    </button>
  </nav>
</template>

<style scoped>
.np-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--np-pagination-gap);
  margin-top: var(--np-pagination-margin-top);
}

.np-pagination__button {
  display: inline-flex;
  width: var(--np-pagination-control-size);
  height: var(--np-pagination-control-size);
  align-items: center;
  justify-content: center;
  border: 1px solid var(--np-pagination-border);
  background: var(--np-color-white);
  color: var(--np-color-navy-950);
  font-family: var(--np-font-display);
  font-size: var(--np-pagination-font-size);
  font-weight: var(--np-weight-semibold);
  line-height: 1;
  cursor: pointer;
  transition: background var(--np-transition-fast), color var(--np-transition-fast), border-color var(--np-transition-fast);
}

.np-pagination__button:hover:not(:disabled),
.np-pagination__button:focus-visible:not(:disabled) {
  border-color: var(--np-color-navy-950);
}

.np-pagination__button--active {
  border-color: var(--np-color-navy-950);
  background: var(--np-color-navy-950);
  color: var(--np-color-white);
}

.np-pagination__button:disabled {
  opacity: .4;
  cursor: default;
}

.np-pagination__button--arrow {
  background: var(--np-home-control-bg);
}

@media (max-width: 560px) {
  .np-pagination {
    gap: var(--np-pagination-gap-mobile);
    margin-top: var(--np-pagination-margin-top-mobile);
  }

  .np-pagination__button {
    width: var(--np-pagination-control-size-mobile);
    height: var(--np-pagination-control-size-mobile);
    font-size: var(--np-pagination-font-size-mobile);
  }
}
</style>
