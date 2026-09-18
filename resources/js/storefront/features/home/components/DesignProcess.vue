<script setup lang="ts">
import { computed } from 'vue';
import ProcessStepCard from './ProcessStepCard.vue';
import type { HomeDesignProcessItem, HomeSection } from '../types/home.types';
import type { StorefrontProduct } from '../../../types/product';
import { sectionItems, sectionTitle } from '../utils/homeSection';

const props=withDefaults(defineProps<{section?:HomeSection;previewProduct?:StorefrontProduct|null}>(),{previewProduct:null});
const fallbackSteps:HomeDesignProcessItem[]=[
  {id:'choose-product',title:'Choose Product',description:'Pick the product, sport, category, or apparel type.'},
  {id:'share-details',title:'Share Custom Details',description:'Send your logo, colors, names, numbers, size list, and quantity.'},
  {id:'review-mockup',title:'Review Mockup',description:'We prepare or review the artwork before production.'},
  {id:'confirm-order',title:'Confirm Order',description:'Approve the final details, price, and timeline.'},
  {id:'production-shipping',title:'Production & Shipping',description:'Your order goes into production and ships to your address.'},
];
const configured=computed(()=>sectionItems<HomeDesignProcessItem>(props.section));
const steps=computed(()=>configured.value.length?configured.value.slice(0,5):fallbackSteps);
const title=computed(()=>sectionTitle(props.section,'HOW TO DESIGN A T-SHIRT USING NEXTPLAY'));
</script>
<template>
  <section class="np-process"><div class="np-process__inner"><h2>{{ title }}</h2><div class="np-process__grid"><ProcessStepCard v-for="(step,index) in steps" :key="step.id || `${index}-${step.title}`" :step="step" :index="index" :image="step.image" :image-alt="step.image_alt" :visual="index===0?'product':'placeholder'" :preview-product="index===0?previewProduct:null" /></div></div></section>
</template>
<style scoped>
.np-process{background:var(--np-color-utility-bg)}.np-process__inner{width:100%;max-width:var(--np-home-process-max-width);margin-inline:auto;padding:var(--np-home-process-padding-top) var(--np-home-process-gutter) var(--np-home-process-padding-bottom)}.np-process h2{margin:0 0 var(--np-home-process-heading-gap);font-family:var(--np-home-process-heading-font-family);font-size:var(--np-home-process-title-size);font-weight:var(--np-home-process-title-weight);line-height:var(--np-home-process-heading-line-height);text-transform:uppercase;color:var(--np-home-text-primary)}.np-process__grid{display:grid;grid-template-columns:repeat(5, minmax(0, 1fr));gap:var(--np-home-process-column-gap);align-items:start}@media(max-width:1180px){.np-process__inner{padding-top:var(--np-home-process-padding-top-tablet);padding-bottom:var(--np-home-process-padding-bottom-tablet)}.np-process__grid{grid-template-columns:repeat(2,minmax(0,1fr));row-gap:var(--np-home-process-row-gap-tablet)}}@media(max-width:640px){.np-process__inner{padding:var(--np-home-process-padding-top-mobile) var(--np-page-gutter) var(--np-home-process-padding-bottom-mobile)}.np-process h2{margin-bottom:var(--np-home-process-heading-gap-mobile)}.np-process__grid{grid-template-columns:1fr;row-gap:var(--np-home-process-row-gap-mobile)}}
</style>
