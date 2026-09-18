<script setup lang="ts">
import { computed } from 'vue';
import AppContainer from '../../../components/ui/AppContainer.vue';
import CategoryCarousel from '../../../components/common/CategoryCarousel.vue';
import type { StorefrontCategory } from '../../../types/category';
import type { HomeCategoryItem, HomeSection } from '../types/home.types';
import { sectionItems, sectionTitle } from '../utils/homeSection';

const props = defineProps<{ categories: StorefrontCategory[]; section?: HomeSection }>();
const wanted = ['jersey','short','t-shirt','tracksuit'];
const fallbackCards = computed(() => wanted.map((needle,index) => props.categories.find(c => `${c.title||''} ${c.short_title||''} ${c.slug||''}`.toLowerCase().includes(needle)) || props.categories[index]).filter(Boolean).slice(0,4) as StorefrontCategory[]);
const cards = computed(() => {
  const configured = sectionItems<HomeCategoryItem>(props.section);
  if (!configured.length) return fallbackCards.value;
  const mapped = configured.map((item) => {
    const match = props.categories.find((category) => Number(category.id) === Number(item.category_id));
    if (!match && !item.title && !item.image) return null;
    return {
      ...(match || {}),
      id: match?.id ?? item.category_id,
      title: item.title || match?.title || match?.short_title || 'Category',
      short_title: item.title || match?.short_title || match?.title || 'Category',
      image: item.image || match?.image,
      banner: item.image || match?.banner || match?.image,
      alt: item.image_alt || match?.alt || item.title || match?.title,
      url: item.url || match?.url || '/products',
    } as StorefrontCategory;
  }).filter(Boolean) as StorefrontCategory[];
  return mapped.length ? mapped.slice(0, 4) : fallbackCards.value;
});
const title = computed(() => sectionTitle(props.section, 'SHOP BY CATEGORY'));
</script>

<template>
  <section class="np-section np-category-section">
    <AppContainer class="np-category-section__container">
      <CategoryCarousel :title="title" :categories="cards" variant="showcase" controls-variant="showcase" />
    </AppContainer>
  </section>
</template>

<style scoped>
.np-category-section{padding:64px 0 72px;overflow:hidden}
.np-category-section__container{max-width:var(--np-showcase-max);padding-inline:var(--np-showcase-gutter)}
@media(max-width:700px){.np-category-section{padding:40px 0 48px}.np-category-section__container{padding-inline:16px}}
</style>
