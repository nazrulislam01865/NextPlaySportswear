import { computed, ref } from 'vue';
import type { StorefrontCategory } from '../../../types/category';
import { fetchMenPage } from '../api/men.api';
import type { MenPageData } from '../types/men.types';

const CARD_DEFINITIONS = [
  { label: 'JERSEYS', needles: ['jersey'], href: '/products?q=men+jersey' },
  { label: 'SHORTS', needles: ['short'], href: '/products?q=men+shorts' },
  { label: 'T-SHIRTS', needles: ['t-shirt', 'tshirt', 'shirt'], href: '/products?q=men+t-shirt' },
  { label: 'TRACKSUITS', needles: ['tracksuit', 'track suit'], href: '/products?q=men+tracksuit' },
] as const;

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

  const categories = computed(() => buildCards(data.value?.categories ?? []));
  const products = computed(() => data.value?.products ?? []);
  const productCount = computed(() => data.value?.productCount ?? 0);
  const currentPage = computed(() => data.value?.currentPage ?? 1);
  const lastPage = computed(() => data.value?.lastPage ?? 1);
  const perPage = computed(() => data.value?.perPage ?? 24);

  async function load(page = 1): Promise<void> {
    loading.value = true;
    error.value = '';

    try {
      data.value = await fetchMenPage(page);
    } catch (caught) {
      error.value = caught instanceof Error ? caught.message : 'Unable to load the Men collection.';
    } finally {
      loading.value = false;
    }
  }

  return {
    data,
    loading,
    error,
    categories,
    products,
    productCount,
    currentPage,
    lastPage,
    perPage,
    load,
  };
}
