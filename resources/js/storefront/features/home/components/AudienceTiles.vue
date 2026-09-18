<script setup lang="ts">
import { computed } from 'vue';
import ImageCategoryTile from '../../../components/common/ImageCategoryTile.vue';
import type { StorefrontCategory } from '../../../types/category';
import type { HomeAudienceItem, HomeSection } from '../types/home.types';
import { sectionItems } from '../utils/homeSection';

const props = defineProps<{ categories: StorefrontCategory[]; section?: HomeSection }>();

const fallbackAudience: HomeAudienceItem[] = [
  { id: 'men', title: 'MEN', url: '/men' },
  { id: 'women', title: 'WOMEN', url: '/products?q=women' },
  { id: 'kids', title: 'KIDS', url: '/products?q=kids' },
];
const fallbackImages: Record<HomeAudienceItem['id'], string> = {
  men: '/storage/storefront/home/baseball.webp',
  women: '/storage/storefront/home/basketball.webp',
  kids: '/storage/storefront/home/soccer.webp',
};

const tiles = computed(() => {
  const configured = sectionItems<HomeAudienceItem>(props.section);
  return fallbackAudience.map((fallback, index) => {
    const item = configured.find((candidate) => candidate.id === fallback.id) ?? fallback;
    const needle = item.id.toLowerCase();
    const match = props.categories.find((category) =>
      `${category.title || ''} ${category.short_title || ''} ${category.slug || ''}`.toLowerCase().includes(needle),
    ) || props.categories[index];

    return {
      title: item.title || fallback.title,
      image: item.image || match?.banner || match?.image || fallbackImages[item.id],
      alt: item.image_alt || match?.alt || item.title || fallback.title,
      href: item.id === 'men' && item.url === '/products?q=men'
        ? '/men'
        : item.url || match?.url || fallback.url,
    };
  });
});
</script>

<template>
  <section class="np-audience">
    <div class="np-audience__container">
      <div class="np-audience__grid">
        <ImageCategoryTile v-for="tile in tiles" :key="tile.title" v-bind="tile" variant="audience" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.np-audience { padding: var(--np-audience-padding-top) 0 var(--np-audience-padding-bottom); }
.np-audience__container { width: 100%; max-width: var(--np-audience-max); margin-inline: auto; padding-inline: var(--np-audience-gutter); }
.np-audience__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--np-audience-gap); }
@media (max-width: 700px) { .np-audience { padding: var(--np-audience-padding-top-mobile) 0 var(--np-audience-padding-bottom-mobile); } .np-audience__container { padding-inline: 12px; } .np-audience__grid { grid-template-columns: 1fr; gap: 12px; } }
</style>
