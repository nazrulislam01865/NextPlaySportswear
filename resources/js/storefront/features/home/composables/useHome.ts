import { computed, ref } from 'vue';
import { fetchHomePage } from '../api/home.api';
import type { HomePageData, HomeSection } from '../types/home.types';

export function useHome() {
  const data = ref<HomePageData | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const sectionsByKey = computed<Record<string, HomeSection>>(() => {
    const entries = (data.value?.sections ?? []).map((section) => [String(section.key ?? section.component ?? ''), section] as const);
    return Object.fromEntries(entries.filter(([key]) => key !== ''));
  });

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      data.value = await fetchHomePage();
      if (data.value?.seo?.title) document.title = data.value.seo.title;
      const description = data.value?.seo?.description;
      const meta = document.querySelector<HTMLMetaElement>('meta[name="description"]');
      if (description && meta) meta.content = description;
    } catch (cause) {
      error.value = cause instanceof Error ? cause.message : 'Unable to load the storefront.';
    } finally {
      loading.value = false;
    }
  }

  return { data, loading, error, sectionsByKey, load };
}
