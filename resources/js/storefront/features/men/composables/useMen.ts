import { computed, ref } from 'vue';
import type { StorefrontCategory } from '../../../types/category';
import { fetchMenPage } from '../api/men.api';
import type {
  MenCatalogFilters,
  MenFilterArrayKey,
  MenFilterChip,
  MenFilterKey,
  MenPageData,
} from '../types/men.types';

const CARD_DEFINITIONS = [
  { label: 'JERSEYS', needles: ['jersey'], href: '/products?q=men+jersey' },
  { label: 'SHORTS', needles: ['short'], href: '/products?q=men+shorts' },
  { label: 'T-SHIRTS', needles: ['t-shirt', 'tshirt', 'shirt'], href: '/products?q=men+t-shirt' },
  { label: 'TRACKSUITS', needles: ['tracksuit', 'track suit'], href: '/products?q=men+tracksuit' },
] as const;

const ARRAY_FILTER_KEYS: MenFilterArrayKey[] = [
  'categories', 'sports', 'product_types', 'colors', 'materials', 'moq', 'customization', 'availability',
];

export function createEmptyMenFilters(): MenCatalogFilters {
  return {
    categories: [],
    sports: [],
    product_types: [],
    colors: [],
    materials: [],
    min_price: null,
    max_price: null,
    moq: [],
    customization: [],
    availability: [],
  };
}

function cloneFilters(source: MenCatalogFilters): MenCatalogFilters {
  return {
    categories: [...source.categories],
    sports: [...source.sports],
    product_types: [...source.product_types],
    colors: [...source.colors],
    materials: [...source.materials],
    min_price: source.min_price,
    max_price: source.max_price,
    moq: [...source.moq],
    customization: [...source.customization],
    availability: [...source.availability],
  };
}

function searchable(category: StorefrontCategory): string {
  return `${category.title ?? ''} ${category.short_title ?? ''} ${category.slug ?? ''}`.toLowerCase();
}

function categoryKey(category: StorefrontCategory): number | string {
  return category.id ?? category.slug ?? searchable(category);
}

function buildCards(categories: StorefrontCategory[]): StorefrontCategory[] {
  const used = new Set<number | string>();

  const primaryCards = CARD_DEFINITIONS.map((definition, index) => {
    const match = categories.find((category) => {
      const key = categoryKey(category);
      return !used.has(key) && definition.needles.some((needle) => searchable(category).includes(needle));
    }) ?? categories.find((category) => {
      const key = categoryKey(category);
      return !used.has(key);
    }) ?? categories[index];

    if (!match) return null;

    const key = categoryKey(match);
    used.add(key);

    return {
      ...match,
      short_title: definition.label,
      title: definition.label,
      url: definition.href,
    };
  }).filter(Boolean) as StorefrontCategory[];

  const remainingCards = categories.filter((category) => {
    const key = categoryKey(category);
    return !used.has(key)
      && Boolean(category.short_title || category.title);
  }).map((category) => ({
    ...category,
    url: category.url || (category.slug ? `/categories/${category.slug}` : '/categories'),
  }));

  return [...primaryCards, ...remainingCards];
}

export function useMen() {
  const data = ref<MenPageData | null>(null);
  const loading = ref(false);
  const error = ref('');
  const filters = ref<MenCatalogFilters>(createEmptyMenFilters());

  const categories = computed(() => buildCards(data.value?.categories ?? []));
  const products = computed(() => data.value?.products ?? []);
  const productCount = computed(() => data.value?.productCount ?? 0);
  const currentPage = computed(() => data.value?.currentPage ?? 1);
  const lastPage = computed(() => data.value?.lastPage ?? 1);
  const perPage = computed(() => data.value?.perPage ?? 24);
  const filterOptions = computed(() => data.value?.filterOptions ?? {
    categories: [], sports: [], product_types: [], colors: [], materials: [],
    price_floor: 0, price_ceiling: 100, moq: [], customization: [], availability: [],
    facet_totals: { product_types: 0, colors: 0, materials: 0, moq: 0, customization: 0, availability: 0 },
  });

  const filterChips = computed<MenFilterChip[]>(() => {
    const options = filterOptions.value;
    const chips: MenFilterChip[] = [];
    const categoryMap = new Map<number, string>();
    options.categories.forEach((parent) => {
      categoryMap.set(parent.id, parent.label);
      parent.children.forEach((child) => categoryMap.set(child.id, child.label));
    });
    const sportMap = new Map(options.sports.map((option) => [option.id, option.label]));
    const maps = {
      product_types: new Map(options.product_types.map((option) => [option.value, option.label])),
      colors: new Map(options.colors.map((option) => [option.value, option.label])),
      materials: new Map(options.materials.map((option) => [option.value, option.label])),
      moq: new Map(options.moq.map((option) => [option.value, option.label])),
      customization: new Map(options.customization.map((option) => [option.value, option.label])),
      availability: new Map(options.availability.map((option) => [option.value, option.label])),
    };

    filters.value.categories.forEach((value) => chips.push({
      id: `categories:${value}`, key: 'categories', value, label: categoryMap.get(value) ?? `Category ${value}`,
    }));
    filters.value.sports.forEach((value) => chips.push({
      id: `sports:${value}`, key: 'sports', value, label: sportMap.get(value) ?? `Sport ${value}`,
    }));
    (Object.keys(maps) as Array<keyof typeof maps>).forEach((key) => {
      filters.value[key].forEach((value) => chips.push({
        id: `${key}:${value}`, key, value, label: maps[key].get(value) ?? value,
      }));
    });

    if (filters.value.min_price !== null) {
      chips.push({ id: 'min_price', key: 'min_price', value: filters.value.min_price, label: `Min £${filters.value.min_price}` });
    }
    if (filters.value.max_price !== null) {
      chips.push({ id: 'max_price', key: 'max_price', value: filters.value.max_price, label: `Max £${filters.value.max_price}` });
    }

    return chips;
  });

  async function load(page = 1): Promise<void> {
    loading.value = true;
    error.value = '';

    try {
      data.value = await fetchMenPage(page, filters.value);
    } catch (caught) {
      error.value = caught instanceof Error ? caught.message : 'Unable to load the Men collection.';
    } finally {
      loading.value = false;
    }
  }

  async function applyFilters(nextFilters: MenCatalogFilters): Promise<void> {
    filters.value = cloneFilters(nextFilters);
    await load(1);
  }

  async function clearFilters(): Promise<void> {
    filters.value = createEmptyMenFilters();
    await load(1);
  }

  async function removeFilter(chip: MenFilterChip): Promise<void> {
    const next = cloneFilters(filters.value);
    if (ARRAY_FILTER_KEYS.includes(chip.key as MenFilterArrayKey)) {
      const key = chip.key as MenFilterArrayKey;
      if (key === 'categories' || key === 'sports') {
        next[key] = next[key].filter((value) => value !== Number(chip.value));
      } else {
        next[key] = next[key].filter((value) => value !== String(chip.value));
      }
    } else if (chip.key === 'min_price' || chip.key === 'max_price') {
      next[chip.key] = null;
    }
    await applyFilters(next);
  }

  return {
    data,
    loading,
    error,
    filters,
    categories,
    products,
    productCount,
    currentPage,
    lastPage,
    perPage,
    filterOptions,
    filterChips,
    load,
    applyFilters,
    clearFilters,
    removeFilter,
  };
}
