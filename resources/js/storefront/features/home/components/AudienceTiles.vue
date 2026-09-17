<script setup lang="ts">
import { computed } from 'vue';
import ImageCategoryTile from '../../../components/common/ImageCategoryTile.vue';
import type { StorefrontCategory } from '../../../types/category';

const props = defineProps<{ categories: StorefrontCategory[] }>();

const definitions = [
  { label: 'MEN', fallback: '/storage/storefront/home/baseball.webp', href: '/products?q=men' },
  { label: 'WOMEN', fallback: '/storage/storefront/home/basketball.webp', href: '/products?q=women' },
  { label: 'KIDS', fallback: '/storage/storefront/home/soccer.webp', href: '/products?q=kids' },
];

const tiles = computed(() => definitions.map((definition, index) => {
  const needle = definition.label.toLowerCase();
  const match = props.categories.find((category) =>
    `${category.title || ''} ${category.short_title || ''} ${category.slug || ''}`.toLowerCase().includes(needle),
  ) || props.categories[index];

  return {
    title: definition.label,
    image: match?.banner || match?.image || definition.fallback,
    alt: match?.alt || definition.label,
    href: match?.url || definition.href,
  };
}));
</script>

<template>
  <section class="np-audience">
    <div class="np-audience__container">
      <div class="np-audience__grid">
        <ImageCategoryTile
          v-for="tile in tiles"
          :key="tile.title"
          v-bind="tile"
          variant="audience"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.np-audience {
  padding: var(--np-audience-padding-top) 0 var(--np-audience-padding-bottom);
}

.np-audience__container {
  width: 100%;
  max-width: var(--np-audience-max);
  margin-inline: auto;
  padding-inline: var(--np-audience-gutter);
}

.np-audience__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--np-audience-gap);
}

@media (max-width: 700px) {
  .np-audience {
    padding: var(--np-audience-padding-top-mobile) 0 var(--np-audience-padding-bottom-mobile);
  }

  .np-audience__container {
    padding-inline: 12px;
  }

  .np-audience__grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}
</style>
