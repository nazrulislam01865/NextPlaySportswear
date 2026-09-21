<script setup lang="ts">
import { computed } from 'vue';
import { Search, UserRound, Heart, ShoppingBag } from 'lucide-vue-next';
import BrandLogo from '../common/BrandLogo.vue';
import AppButton from '../ui/AppButton.vue';
import DesktopNavigation from './DesktopNavigation.vue';
import MobileNavigation from './MobileNavigation.vue';
import { useStorefrontStore } from '../../stores/storefront.store';

const storefront = useStorefrontStore();
const headerNavigation = computed(() => storefront.navigation.items ?? []);
const actions = computed(() => storefront.header.actions ?? {});
const search = computed(() => actions.value.search ?? {});
const quote = computed(() => actions.value.quote ?? {});
const showSearch = computed(() => search.value.enabled !== false && Boolean(search.value.url));
const showAccount = computed(() => actions.value.account_enabled !== false);
const showWishlist = computed(() => actions.value.wishlist_enabled !== false);
const showCart = computed(() => actions.value.cart_enabled !== false);
const showQuote = computed(() => quote.value.enabled !== false && Boolean(quote.value.label && quote.value.url));
const cartLabel = () => new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
}).format(storefront.cartTotal);
</script>

<template>
  <header class="np-header">
    <div class="np-header__inner">
      <div class="np-header__mobile"><MobileNavigation :items="headerNavigation" /></div>
      <BrandLogo class="np-header__logo" />
      <DesktopNavigation class="np-header__nav" :items="headerNavigation" />
      <div class="np-header__actions">
        <a v-if="showSearch" :href="search.url || '/products'" class="np-header__action np-header__search"><Search :size="19" :stroke-width="1.45"/><span>{{ search.label || 'Search' }}</span></a>
        <span v-if="showSearch && (showAccount || showWishlist || showCart || showQuote)" class="np-header__divider" aria-hidden="true"></span>
        <a v-if="showAccount" :href="storefront.customer ? '/account' : '/login'" class="np-header__action" aria-label="Account"><UserRound :size="20" :stroke-width="1.35"/></a>
        <span v-if="showAccount && (showWishlist || showCart || showQuote)" class="np-header__divider" aria-hidden="true"></span>
        <a v-if="showWishlist" href="/wishlist" class="np-header__action np-header__count" aria-label="Wishlist"><Heart :size="20" :stroke-width="1.35"/><small v-if="storefront.wishlistCount">{{ storefront.wishlistCount }}</small></a>
        <span v-if="showWishlist && (showCart || showQuote)" class="np-header__divider" aria-hidden="true"></span>
        <a v-if="showCart" href="/cart" class="np-header__action np-header__cart" aria-label="Cart"><ShoppingBag :size="20" :stroke-width="1.35"/><span>{{ cartLabel() }}</span></a>
        <AppButton v-if="showQuote" :href="quote.url || '/bulk-quote'" variant="navy" size="header" hover-effect="chevrons">{{ quote.label }}</AppButton>
      </div>
    </div>
  </header>
</template>

<style scoped>
.np-header {
  position: sticky;
  top: 0;
  z-index: var(--np-header-sticky-z-index);
  background: var(--np-color-white);
  border-bottom: 1px solid var(--np-color-header-border);
}
.np-header__inner {
  width: 100%;
  max-width: var(--np-first-fold-max);
  min-height: var(--np-header-height);
  margin: 0 auto;
  padding-inline: var(--np-first-fold-gutter);
  box-sizing: border-box;
  display: grid;
  grid-template-columns: minmax(210px, 270px) minmax(0, 1fr) auto;
  align-items: center;
  gap: clamp(28px, 2.5vw, 54px);
}
.np-header__logo { justify-self: start; }
.np-header__actions { display: flex; align-items: center; gap: var(--np-header-actions-gap); color: var(--np-color-navy-950); }
.np-header__action {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: var(--np-header-action-gap);
  color: inherit;
  font-family: var(--np-font-body);
  font-size: var(--np-header-action-font-size);
  font-weight: var(--np-header-action-font-weight);
  white-space: nowrap;
}
.np-header__divider { width: 1px; height: 20px; background: var(--np-color-border); }
.np-header__count small {
  position: absolute;
  top: -9px;
  right: -9px;
  min-width: 16px;
  height: 16px;
  padding: 0 3px;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: var(--np-color-orange);
  color: var(--np-color-white);
  font-size: 8px;
}
.np-header__mobile { display: none; }

@media (max-width: 1250px) {
  .np-header__inner { grid-template-columns: 190px 1fr auto; gap: 20px; }
  .np-header__actions { gap: var(--np-header-actions-gap); }
  .np-header__divider { display: none; }
}
@media (max-width: 1000px) {
  .np-header__inner { grid-template-columns: auto 1fr auto; gap: 14px; }
  .np-header__mobile { display: block; }
  .np-header__logo { justify-self: center; }
  .np-header__search span, .np-header__cart span, .np-header__actions :deep(.np-button) { display: none; }
}
@media (max-width: 560px) {
  .np-header__inner { min-height: 66px; padding-inline: 16px; }
  .np-header__actions { gap: 9px; }
}
</style>
