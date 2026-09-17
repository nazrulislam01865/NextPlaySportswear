<script setup lang="ts">
import { computed } from 'vue';
import AppContainer from '../../../components/ui/AppContainer.vue';
import CategoryCarousel from '../../../components/common/CategoryCarousel.vue';
import type { StorefrontCategory } from '../../../types/category';

const props=defineProps<{categories:StorefrontCategory[]}>();
const wanted=['jersey','short','t-shirt','tracksuit'];
const cards=computed(()=>wanted.map((needle,index)=>props.categories.find(c=>`${c.title||''} ${c.short_title||''} ${c.slug||''}`.toLowerCase().includes(needle))||props.categories[index]).filter(Boolean).slice(0,4) as StorefrontCategory[]);
</script>

<template>
  <section class="np-section np-category-section">
    <AppContainer class="np-category-section__container">
      <CategoryCarousel title="SHOP BY CATEGORY" :categories="cards" variant="showcase" controls-variant="showcase" />
    </AppContainer>
  </section>
</template>

<style scoped>
.np-category-section{padding:64px 0 72px;overflow:hidden}
.np-category-section__container{max-width:var(--np-showcase-max);padding-inline:var(--np-showcase-gutter)}
@media(max-width:700px){.np-category-section{padding:40px 0 48px}.np-category-section__container{padding-inline:16px}}
</style>
