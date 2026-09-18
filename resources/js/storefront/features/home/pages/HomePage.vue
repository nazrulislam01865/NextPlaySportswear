<script setup lang="ts">
import { computed, onMounted } from 'vue';
import AppContainer from '../../../components/ui/AppContainer.vue';
import AppSkeleton from '../../../components/ui/AppSkeleton.vue';
import UtilityBar from '../../../components/layout/UtilityBar.vue';
import StorefrontHeader from '../../../components/layout/StorefrontHeader.vue';
import StorefrontFooter from '../../../components/layout/StorefrontFooter.vue';
import HomeHero from '../components/HomeHero.vue';
import AudienceTiles from '../components/AudienceTiles.vue';
import ShopBySport from '../components/ShopBySport.vue';
import NewArrivals from '../components/NewArrivals.vue';
import ShopByCategory from '../components/ShopByCategory.vue';
import BestChoices from '../components/BestChoices.vue';
import SeasonSaleBanner from '../components/SeasonSaleBanner.vue';
import MakeItYours from '../components/MakeItYours.vue';
import DesignProcess from '../components/DesignProcess.vue';
import { useHome } from '../composables/useHome';
import { useHomeActions } from '../composables/useHomeActions';
import { useStorefrontStore } from '../../../stores/storefront.store';
import { sectionIsActive } from '../utils/homeSection';

const storefront = useStorefrontStore();
const home = useHome();
const actions = useHomeActions();
const section = (key: string) => home.sectionsByKey.value[key];
const categories = computed(() => home.data.value?.categories ?? []);
const latestProducts = computed(() => home.data.value?.latest_products ?? []);
const featuredProducts = computed(() => home.data.value?.featured_products ?? []);
const bestSellingProducts = computed(() => home.data.value?.best_selling_products ?? []);
const customizableProducts = computed(() => [...featuredProducts.value, ...latestProducts.value].filter((product, index, list) => list.findIndex((item) => item.id === product.id) === index));
onMounted(async () => { await Promise.allSettled([storefront.bootstrap(), home.load()]); });
</script>
<template>
  <div class="np-page"><UtilityBar /><StorefrontHeader />
    <main id="main-content">
      <template v-if="home.data.value">
        <HomeHero v-if="sectionIsActive(section('hero'))" :slides="home.data.value.slides" :section="section('hero')" />
        <AudienceTiles v-if="sectionIsActive(section('audience'))" :categories="categories" :section="section('audience')" />
        <ShopBySport v-if="sectionIsActive(section('shop_by_sport'))" :sports="home.data.value.sports" :section="section('shop_by_sport')" />
        <NewArrivals v-if="sectionIsActive(section('new_arrivals'))" :products="latestProducts" :section="section('new_arrivals')" :busy-product-id="actions.busyProductId.value" @add="actions.addToCart" @wishlist="actions.addToWishlist" />
        <ShopByCategory v-if="sectionIsActive(section('shop_by_category'))" :categories="categories" :section="section('shop_by_category')" />
        <BestChoices v-if="sectionIsActive(section('best_choices'))" :section="section('best_choices')" :featured="featuredProducts" :best-selling="bestSellingProducts" :trending="latestProducts" :busy-product-id="actions.busyProductId.value" @add="actions.addToCart" @wishlist="actions.addToWishlist" />
        <SeasonSaleBanner v-if="sectionIsActive(section('season_sale'))" :section="section('season_sale')" />
        <MakeItYours v-if="sectionIsActive(section('make_it_yours'))" :section="section('make_it_yours')" :products="customizableProducts" :busy-product-id="actions.busyProductId.value" @add="actions.addToCart" @wishlist="actions.addToWishlist" />
        <DesignProcess v-if="sectionIsActive(section('design_process'))" :section="section('design_process')" :preview-product="latestProducts[0] || null" />
      </template>
      <template v-else-if="home.loading.value"><AppSkeleton class="np-home-skeleton np-home-skeleton--hero" /><AppContainer><AppSkeleton class="np-home-skeleton" /></AppContainer><AppContainer><AppSkeleton class="np-home-skeleton" /></AppContainer></template>
      <section v-else class="np-home-error"><AppContainer><h1>We couldn't load the storefront.</h1><p>{{ home.error.value || 'Please refresh the page and try again.' }}</p><button type="button" @click="home.load">TRY AGAIN</button></AppContainer></section>
    </main>
    <p v-if="actions.message.value" class="np-home-toast" role="status">{{ actions.message.value }}</p><StorefrontFooter />
  </div>
</template>
<style scoped>
.np-home-skeleton{margin:48px 0;min-height:360px}.np-home-skeleton--hero{margin:0;min-height:560px}.np-home-error{padding:100px 0;text-align:center}.np-home-error h1{font-family:var(--np-font-display);font-size:30px;color:var(--np-color-navy-950);text-transform:uppercase}.np-home-error p{margin-top:10px;color:var(--np-color-text-muted)}.np-home-error button{margin-top:20px;min-height:38px;padding:0 18px;background:var(--np-color-orange);color:#fff;font-family:var(--np-font-display)}.np-home-toast{position:fixed;z-index:100;right:20px;bottom:20px;margin:0;padding:11px 16px;background:var(--np-color-navy-950);color:#fff;font-size:11px;box-shadow:var(--np-shadow-card)}
</style>
