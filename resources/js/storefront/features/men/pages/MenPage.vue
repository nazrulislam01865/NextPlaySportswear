<script setup lang="ts">
import { onMounted } from 'vue';
import UtilityBar from '../../../components/layout/UtilityBar.vue';
import StorefrontHeader from '../../../components/layout/StorefrontHeader.vue';
import StorefrontFooter from '../../../components/layout/StorefrontFooter.vue';
import Breadcrumbs from '../../../components/common/Breadcrumbs.vue';
import CategoryCarousel from '../../../components/common/CategoryCarousel.vue';
import ProductGrid from '../../../components/common/ProductGrid.vue';
import AppPagination from '../../../components/ui/AppPagination.vue';
import AppSkeleton from '../../../components/ui/AppSkeleton.vue';
import CatalogFilterSidebar from '../../catalog/components/CatalogFilterSidebar.vue';
import CatalogFilterChips from '../../catalog/components/CatalogFilterChips.vue';
import { useStorefrontStore } from '../../../stores/storefront.store';
import { useProductActions } from '../../../composables/useProductActions';
import { useMen } from '../composables/useMen';

const storefront = useStorefrontStore();
const men = useMen();
const actions = useProductActions();
const breadcrumbs = [
  { label: 'Home', href: '/' },
  { label: 'Shop', href: '/products' },
  { label: 'Men' },
];

onMounted(async () => {
  await Promise.allSettled([storefront.bootstrap(), men.load()]);
});
</script>

<template>
  <div class="np-page np-men-page">
    <UtilityBar />
    <StorefrontHeader />

    <main id="main-content" class="np-men-main">
      <div class="np-men-main__inner">
        <Breadcrumbs :items="breadcrumbs" />

        <template v-if="men.data.value">
          <CategoryCarousel
            class="np-men-category-carousel"
            title="MEN"
            :title-meta="`${men.productCount.value} Products`"
            :categories="men.categories.value"
            variant="showcase"
            controls-variant="showcase"
            edge-aware-controls
            contained
          />

          <div class="np-men-catalog-layout">
            <CatalogFilterSidebar
              :filter-options="men.filterOptions.value"
              :model-value="men.filters.value"
              :disabled="men.loading.value"
              @change="men.applyFilters"
            />

            <section class="np-men-catalog-results" aria-label="Men products">
              <CatalogFilterChips
                :chips="men.filterChips.value"
                :disabled="men.loading.value"
                @remove="men.removeFilter"
                @clear="men.clearFilters"
              />

              <ProductGrid
                class="np-men-product-grid"
                :products="men.products.value"
                :busy-product-id="actions.busyProductId.value"
                card-variant="new-arrivals"
                @add="actions.addToCart"
                @wishlist="actions.addToWishlist"
              />

              <p v-if="!men.loading.value && men.products.value.length === 0" class="np-men-no-results">
                No products match the selected filters.
              </p>

              <AppPagination
                :current-page="men.currentPage.value"
                :last-page="men.lastPage.value"
                :disabled="men.loading.value"
                @change="men.load"
              />
            </section>
          </div>
        </template>

        <div v-else-if="men.loading.value" class="np-men-loading" aria-label="Loading Men collection">
          <AppSkeleton class="np-men-loading__heading" />
          <div class="np-men-loading__grid">
            <AppSkeleton v-for="index in 4" :key="index" class="np-men-loading__card" />
          </div>
        </div>

        <section v-else class="np-men-error" role="alert">
          <h1>MEN</h1>
          <p>{{ men.error.value || 'We could not load the Men collection.' }}</p>
          <button type="button" @click="men.load">TRY AGAIN</button>
        </section>
      </div>
    </main>

    <p v-if="actions.message.value" class="np-men-toast" role="status">{{ actions.message.value }}</p>
    <StorefrontFooter />
  </div>
</template>

<style scoped>
.np-men-main {
  overflow: hidden;
  padding: var(--np-men-content-padding-top) 0 var(--np-men-content-padding-bottom);
  background: var(--np-color-white);
}
.np-men-main__inner {
  width: 100%;
  max-width: var(--np-men-content-max);
  margin-inline: auto;
  padding-inline: var(--np-men-content-gutter);
}
.np-men-main :deep(.np-breadcrumbs) { font-size: var(--np-men-breadcrumb-size); }
.np-men-category-carousel { margin-top: var(--np-men-heading-offset); }
.np-men-category-carousel :deep(.np-section-head) { margin-bottom: var(--np-men-cards-gap-top); }
.np-men-category-carousel :deep(.np-section-head__left) { gap: var(--np-men-title-count-gap); }
.np-men-category-carousel :deep(.np-section-title) {
  color: var(--np-color-navy-950);
  font-family: var(--np-font-display);
  font-size: var(--np-men-page-title-size);
  font-weight: var(--np-men-page-title-weight);
  line-height: var(--np-men-page-title-line-height);
}
.np-men-category-carousel :deep(.np-category-carousel__title-meta) {
  padding-bottom: var(--np-men-product-count-baseline-offset);
  color: var(--np-color-text-muted);
  font-family: var(--np-font-body);
  font-size: var(--np-men-product-count-size);
  font-weight: var(--np-men-product-count-weight);
  line-height: var(--np-men-product-count-line-height);
  white-space: nowrap;
}

.np-men-catalog-layout {
  display: grid;
  grid-template-columns: calc(25% - var(--np-men-filter-layout-gap)) var(--np-men-product-grid-desktop-width);
  column-gap: var(--np-men-filter-layout-gap);
  align-items: start;
  margin-top: var(--np-men-product-grid-margin-top);
}

.np-men-catalog-results {
  min-width: 0;
}

.np-men-product-grid {
  --np-product-grid-gap: var(--np-men-product-grid-gap);
  --np-product-grid-margin-top: 0;
}

.np-men-no-results {
  margin: 36px 0 0;
  color: var(--np-color-text-muted);
  font-family: var(--np-font-body);
  font-size: var(--np-type-body-2-size);
}

.np-men-loading { margin-top: var(--np-men-heading-offset); }
.np-men-loading__heading { width: 280px; min-height: 52px; margin-bottom: var(--np-men-cards-gap-top); }
.np-men-loading__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.np-men-loading__card { aspect-ratio: 1.04; }
.np-men-error { padding: 64px 0 120px; }
.np-men-error h1 {
  margin: 0;
  color: var(--np-color-navy-950);
  font-family: var(--np-font-display);
  font-size: var(--np-men-page-title-size);
  font-weight: var(--np-men-page-title-weight);
}
.np-men-error p {
  margin: 12px 0 0;
  color: var(--np-color-text-muted);
  font-family: var(--np-font-body);
  font-size: var(--np-type-body-2-size);
}
.np-men-toast {
  position:fixed;
  z-index:100;
  right:20px;
  bottom:20px;
  margin:0;
  padding:11px 16px;
  background:var(--np-color-navy-950);
  color:var(--np-color-white);
  font-family:var(--np-font-body);
  font-size:var(--np-type-body-2-size);
  box-shadow:var(--np-shadow-card);
}
.np-men-error button {
  min-height: 40px;
  margin-top: 20px;
  padding: 0 18px;
  background: var(--np-color-orange);
  color: var(--np-color-white);
  font-family: var(--np-font-display);
  font-weight: var(--np-weight-bold);
}

@media (max-width: 1180px) {
  .np-men-catalog-layout {
    grid-template-columns: 290px minmax(0, 1fr);
    column-gap: 24px;
  }
}

@media (max-width: 900px) {
  .np-men-main { padding-top: var(--np-men-content-padding-top-tablet); }
  .np-men-loading__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .np-men-catalog-layout {
    grid-template-columns: 1fr;
    row-gap: 28px;
  }
}

@media (max-width: 700px) {
  .np-men-main { padding: var(--np-men-content-padding-top-mobile) 0 var(--np-men-content-padding-bottom-mobile); }
  .np-men-main__inner { padding-inline: var(--np-men-content-gutter-mobile); }
  .np-men-category-carousel { margin-top: var(--np-men-heading-offset-mobile); }
  .np-men-category-carousel :deep(.np-section-title) { font-size: var(--np-men-page-title-size-mobile); }
  .np-men-category-carousel :deep(.np-category-carousel__title-meta) { font-size: var(--np-men-product-count-size-mobile); }
  .np-men-loading__grid { grid-template-columns: 1fr; }
}
</style>
