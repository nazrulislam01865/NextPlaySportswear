<script setup lang="ts">
withDefaults(defineProps<{
  label: string;
  count?: number | null;
  checked: boolean;
  colorHex?: string | null;
  disabled?: boolean;
}>(), {
  count: null,
  colorHex: null,
  disabled: false,
});

const emit = defineEmits<{ change: [] }>();
</script>

<template>
  <label class="np-catalog-filter-option" :class="{ 'np-catalog-filter-option--disabled': disabled }">
    <input
      class="np-catalog-filter-option__input"
      type="checkbox"
      :checked="checked"
      :disabled="disabled"
      @change="emit('change')"
    >
    <span class="np-catalog-filter-option__box" aria-hidden="true" />
    <span
      v-if="colorHex"
      class="np-catalog-filter__color-dot"
      :style="{ backgroundColor: colorHex }"
      aria-hidden="true"
    />
    <span class="np-catalog-filter-option__label">{{ label }}</span>
    <span v-if="count !== null" class="np-catalog-filter-option__count">({{ count }})</span>
  </label>
</template>

<style scoped>
.np-catalog-filter-option {
  display: flex;
  min-height: var(--np-men-filter-option-row-height);
  align-items: center;
  gap: var(--np-men-filter-option-gap);
  color: var(--np-color-navy-950);
  cursor: pointer;
  font-family: var(--np-font-body);
  font-size: var(--np-men-filter-option-size);
  font-weight: var(--np-men-filter-option-weight);
  line-height: 1.25;
}

.np-catalog-filter-option--disabled {
  cursor: default;
  opacity: .55;
}

.np-catalog-filter-option__input {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
}

.np-catalog-filter-option__box {
  display: inline-flex;
  width: var(--np-men-filter-checkbox-size);
  height: var(--np-men-filter-checkbox-size);
  flex: 0 0 var(--np-men-filter-checkbox-size);
  align-items: center;
  justify-content: center;
  border: 1px solid var(--np-men-filter-checkbox-border);
  position: relative;
  background: var(--np-color-white);
}

.np-catalog-filter-option__box::after {
  width: 8px;
  height: 4px;
  border: solid var(--np-color-white);
  border-width: 0 0 2px 2px;
  content: '';
  opacity: 0;
  transform: translateY(-1px) rotate(-45deg);
  transition: opacity var(--np-transition-base);
}

.np-catalog-filter-option__input:checked + .np-catalog-filter-option__box {
  border-color: var(--np-color-navy-950);
  background: var(--np-color-navy-950);
}

.np-catalog-filter-option__input:checked + .np-catalog-filter-option__box::after {
  opacity: 1;
}

.np-catalog-filter__color-dot {
  width: var(--np-men-filter-color-dot-size);
  height: var(--np-men-filter-color-dot-size);
  flex: 0 0 var(--np-men-filter-color-dot-size);
  border-radius: 50%;
}

.np-catalog-filter-option__label {
  position: relative;
  min-width: 0;
  width: fit-content;
}

.np-catalog-filter-option__label::after {
  position: absolute;
  right: 0;
  bottom: calc(-1 * var(--np-mega-menu-underline-offset));
  left: 0;
  height: var(--np-mega-menu-underline-height);
  background: currentColor;
  content: '';
  pointer-events: none;
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform var(--np-transition-base);
}

.np-catalog-filter-option:hover .np-catalog-filter-option__label::after,
.np-catalog-filter-option:focus-within .np-catalog-filter-option__label::after {
  transform: scaleX(1);
}

.np-catalog-filter-option__count {
  margin-left: -4px;
  color: var(--np-men-filter-count-color);
  font-size: var(--np-men-filter-count-size);
}
</style>
