<script setup lang="ts">
import ProductCard from './ProductCard.vue';
import type { StorefrontProduct } from '../../types/product';

withDefaults(defineProps<{
  products: StorefrontProduct[];
  busyProductId?: number | null;
  forceCustomize?: boolean;
  cardVariant?: 'default' | 'new-arrivals';
  showPrice?: boolean;
  showBadges?: boolean;
  desktopLeadingSpacer?: boolean;
}>(), {
  busyProductId: null,
  forceCustomize: false,
  cardVariant: 'new-arrivals',
  showPrice: true,
  showBadges: true,
  desktopLeadingSpacer: false,
});

const emit = defineEmits<{
  add: [product: StorefrontProduct];
  wishlist: [product: StorefrontProduct];
}>();
</script>

<template>
  <div
    class="np-product-grid"
    :class="{ 'np-product-grid--desktop-leading-spacer': desktopLeadingSpacer }"
  >
    <div
      v-for="product in products"
      :key="product.id"
      class="np-product-grid__cell"
    >
      <ProductCard
        :product="product"
        :busy="busyProductId === product.id"
        :force-customize="forceCustomize"
        :variant="cardVariant"
        :show-price="showPrice"
        :show-badges="showBadges"
        @add="emit('add', $event)"
        @wishlist="emit('wishlist', $event)"
      />
    </div>
  </div>
</template>

<style scoped>
.np-product-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--np-product-grid-gap, 10px);
  width: 100%;
  margin: var(--np-product-grid-margin-top, 0) auto 0;
}

.np-product-grid__cell {
  min-width: 0;
}


@media (min-width: 1580px) {
  .np-product-grid--desktop-leading-spacer {
    width: var(--np-product-grid-desktop-width, 75%);
    margin-left: auto;
    margin-right: 0;
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 1180px) {
  .np-product-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 860px) {
  .np-product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 560px) {
  .np-product-grid { grid-template-columns: 1fr; }
}
</style>
