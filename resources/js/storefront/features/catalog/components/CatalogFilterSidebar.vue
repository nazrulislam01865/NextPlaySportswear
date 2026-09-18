<script setup lang="ts">
import { computed, reactive } from 'vue';
import CatalogFilterOption from './CatalogFilterOption.vue';
import CatalogFilterSection from './CatalogFilterSection.vue';
import type {
  MenCatalogFilters,
  MenCategoryFilterOption,
  MenFilterOptions,
} from '../../men/types/men.types';

const props = withDefaults(defineProps<{
  filterOptions: MenFilterOptions;
  modelValue: MenCatalogFilters;
  disabled?: boolean;
}>(), {
  disabled: false,
});

const emit = defineEmits<{
  change: [filters: MenCatalogFilters];
}>();

const expanded = reactive<Record<string, boolean>>({});

const categoryDefinitions = [
  { key: 'accessories', label: 'ACCESSORIES', needles: ['accessor'] },
  { key: 'bags', label: 'BAGS', needles: ['bag'] },
  { key: 'drinkware', label: 'DRINKWARE', needles: ['drink', 'bottle'] },
  { key: 'headwear', label: 'HEADWEAR', needles: ['headwear', 'cap', 'hat'] },
  { key: 'performance-apparel', label: 'PERFORMANCE APPAREL', needles: ['performance apparel', 'performance'] },
] as const;

function cloneFilters(): MenCatalogFilters {
  return {
    categories: [...props.modelValue.categories],
    sports: [...props.modelValue.sports],
    product_types: [...props.modelValue.product_types],
    colors: [...props.modelValue.colors],
    materials: [...props.modelValue.materials],
    min_price: props.modelValue.min_price,
    max_price: props.modelValue.max_price,
    moq: [...props.modelValue.moq],
    customization: [...props.modelValue.customization],
    availability: [...props.modelValue.availability],
  };
}

function toggleSection(key: string): void {
  expanded[key] = !expanded[key];
}

function categorySearchText(category: MenCategoryFilterOption): string {
  return `${category.label} ${category.slug ?? ''}`.toLowerCase();
}

function categoryFor(needles: readonly string[]): MenCategoryFilterOption | null {
  return props.filterOptions.categories.find((category) => {
    const text = categorySearchText(category);
    return needles.some((needle) => text.includes(needle));
  }) ?? null;
}

const categorySections = computed(() => categoryDefinitions.map((definition) => ({
  ...definition,
  category: categoryFor(definition.needles),
})));

function isSelected(key: 'colors' | 'materials' | 'product_types' | 'moq' | 'customization' | 'availability', value: string): boolean {
  return props.modelValue[key].includes(value);
}

function toggleStringFilter(key: 'colors' | 'materials' | 'product_types' | 'moq' | 'customization' | 'availability', value: string): void {
  const next = cloneFilters();
  next[key] = next[key].includes(value)
    ? next[key].filter((current) => current !== value)
    : [...next[key], value];
  emit('change', next);
}

function toggleNumberFilter(key: 'categories' | 'sports', value: number): void {
  const next = cloneFilters();
  next[key] = next[key].includes(value)
    ? next[key].filter((current) => current !== value)
    : [...next[key], value];
  emit('change', next);
}

function categoryItems(category: MenCategoryFilterOption | null): Array<{ id: number; label: string; count: number }> {
  if (!category) return [];
  if (category.children.length) return category.children;
  return [{ id: category.id, label: category.label, count: category.count }];
}

function priceDisplay(kind: 'min' | 'max'): number {
  const selected = kind === 'min' ? props.modelValue.min_price : props.modelValue.max_price;
  if (selected !== null) return selected;
  return kind === 'min' ? props.filterOptions.price_floor : props.filterOptions.price_ceiling;
}

function commitPrice(kind: 'min' | 'max', event: Event): void {
  const raw = Number((event.target as HTMLInputElement).value);
  if (!Number.isFinite(raw) || raw < 0) return;

  const next = cloneFilters();
  if (kind === 'min') {
    next.min_price = raw <= props.filterOptions.price_floor ? null : raw;
    if (next.max_price !== null && next.min_price !== null && next.min_price > next.max_price) {
      next.max_price = next.min_price;
    }
  } else {
    next.max_price = raw >= props.filterOptions.price_ceiling ? null : raw;
    if (next.min_price !== null && next.max_price !== null && next.max_price < next.min_price) {
      next.min_price = next.max_price;
    }
  }
  emit('change', next);
}

</script>

<template>
  <aside class="np-catalog-filter" aria-label="Product filters">
    <h2 class="np-catalog-filter__title">FILTER</h2>

    <CatalogFilterSection
      title="COLOR"
      :count="filterOptions.facet_totals.colors || null"
      :expanded="Boolean(expanded.color)"
      :disabled="disabled"
      @toggle="toggleSection('color')"
    >
      <CatalogFilterOption
        v-for="option in filterOptions.colors"
        :key="option.value"
        :label="option.label"
        :count="option.count"
        :checked="isSelected('colors', option.value)"
        :color-hex="option.color_hex"
        :disabled="disabled"
        @change="toggleStringFilter('colors', option.value)"
      />
    </CatalogFilterSection>

    <CatalogFilterSection
      title="PRICE"
      :expanded="Boolean(expanded.price)"
      :disabled="disabled"
      @toggle="toggleSection('price')"
    >
      <div class="np-catalog-filter__price-grid">
        <label class="np-catalog-filter__price-field">
          <span>Minimum £</span>
          <input
            type="number"
            min="0"
            :max="priceDisplay('max')"
            :value="priceDisplay('min')"
            :disabled="disabled"
            @change="commitPrice('min', $event)"
          >
        </label>
        <label class="np-catalog-filter__price-field">
          <span>Maximum £</span>
          <input
            type="number"
            :min="priceDisplay('min')"
            :value="priceDisplay('max')"
            :disabled="disabled"
            @change="commitPrice('max', $event)"
          >
        </label>
      </div>
    </CatalogFilterSection>

    <CatalogFilterSection
      v-for="section in categorySections"
      :key="section.key"
      :title="section.label"
      :count="section.category?.count ?? null"
      :expanded="Boolean(expanded[section.key])"
      :disabled="disabled"
      @toggle="toggleSection(section.key)"
    >
      <CatalogFilterOption
        v-for="option in categoryItems(section.category)"
        :key="option.id"
        :label="option.label"
        :count="option.count"
        :checked="modelValue.categories.includes(option.id)"
        :disabled="disabled"
        @change="toggleNumberFilter('categories', option.id)"
      />
    </CatalogFilterSection>

    <CatalogFilterSection
      title="SPORT"
      :expanded="Boolean(expanded.sport)"
      :disabled="disabled"
      @toggle="toggleSection('sport')"
    >
      <CatalogFilterOption
        v-for="option in filterOptions.sports"
        :key="option.id"
        :label="option.label"
        :count="option.count"
        :checked="modelValue.sports.includes(option.id)"
        :disabled="disabled"
        @change="toggleNumberFilter('sports', option.id)"
      />
    </CatalogFilterSection>

    <CatalogFilterSection
      title="PRODUCT TYPE"
      :expanded="Boolean(expanded['product-type'])"
      :disabled="disabled"
      @toggle="toggleSection('product-type')"
    >
      <CatalogFilterOption
        v-for="option in filterOptions.product_types"
        :key="option.value"
        :label="option.label"
        :count="option.count"
        :checked="isSelected('product_types', option.value)"
        :disabled="disabled"
        @change="toggleStringFilter('product_types', option.value)"
      />
    </CatalogFilterSection>

    <CatalogFilterSection
      title="MINIMUM ORDER QUANTITY"
      :expanded="Boolean(expanded.moq)"
      :disabled="disabled"
      @toggle="toggleSection('moq')"
    >
      <CatalogFilterOption
        v-for="option in filterOptions.moq"
        :key="option.value"
        :label="option.label"
        :count="option.count"
        :checked="isSelected('moq', option.value)"
        :disabled="disabled"
        @change="toggleStringFilter('moq', option.value)"
      />
    </CatalogFilterSection>

    <CatalogFilterSection
      title="CUSTOMIZATION"
      :expanded="Boolean(expanded.customization)"
      :disabled="disabled"
      @toggle="toggleSection('customization')"
    >
      <CatalogFilterOption
        v-for="option in filterOptions.customization"
        :key="option.value"
        :label="option.label"
        :count="option.count"
        :checked="isSelected('customization', option.value)"
        :disabled="disabled"
        @change="toggleStringFilter('customization', option.value)"
      />
    </CatalogFilterSection>

    <CatalogFilterSection
      title="FABRIC / MATERIAL"
      :expanded="Boolean(expanded.materials)"
      :disabled="disabled"
      @toggle="toggleSection('materials')"
    >
      <CatalogFilterOption
        v-for="option in filterOptions.materials"
        :key="option.value"
        :label="option.label"
        :count="option.count"
        :checked="isSelected('materials', option.value)"
        :disabled="disabled"
        @change="toggleStringFilter('materials', option.value)"
      />
    </CatalogFilterSection>

    <CatalogFilterSection
      title="AVAILABILITY"
      :expanded="Boolean(expanded.availability)"
      :disabled="disabled"
      @toggle="toggleSection('availability')"
    >
      <CatalogFilterOption
        v-for="option in filterOptions.availability"
        :key="option.value"
        :label="option.label"
        :count="option.count"
        :checked="isSelected('availability', option.value)"
        :disabled="disabled"
        @change="toggleStringFilter('availability', option.value)"
      />
    </CatalogFilterSection>
  </aside>
</template>

<style scoped>
.np-catalog-filter {
  min-width: 0;
  color: var(--np-color-navy-950);
}

.np-catalog-filter__title {
  display: flex;
  min-height: var(--np-men-filter-toolbar-height);
  align-items: flex-start;
  margin: 0;
  border-bottom: 1px solid var(--np-men-filter-border);
  color: var(--np-color-navy-950);
  font-family: var(--np-font-display);
  font-size: var(--np-men-filter-title-size);
  font-weight: var(--np-men-filter-title-weight);
  line-height: 1;
  text-transform: uppercase;
}

.np-catalog-filter__price-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--np-men-filter-price-gap);
  padding-top: var(--np-men-filter-price-top-gap);
}

.np-catalog-filter__price-field {
  display: grid;
  gap: var(--np-men-filter-price-label-gap);
  color: var(--np-color-navy-950);
  font-family: var(--np-font-body);
  font-size: var(--np-men-filter-price-label-size);
  font-weight: var(--np-men-filter-price-label-weight);
}

.np-catalog-filter__price-field input {
  width: 100%;
  height: var(--np-men-filter-price-input-height);
  box-sizing: border-box;
  border: 1px solid var(--np-men-filter-input-border);
  border-radius: 0;
  outline: 0;
  background: var(--np-color-white);
  color: var(--np-color-navy-950);
  font-family: var(--np-font-body);
  font-size: var(--np-men-filter-option-size);
  padding: 0 var(--np-men-filter-price-input-padding);
}

.np-catalog-filter__price-field input:focus {
  border-color: var(--np-color-navy-950);
}

.np-catalog-filter__price-field input::-webkit-inner-spin-button,
.np-catalog-filter__price-field input::-webkit-outer-spin-button {
  margin: 0;
  appearance: none;
}

@media (max-width: 900px) {
  .np-catalog-filter__title {
    min-height: 52px;
  }
}
</style>
