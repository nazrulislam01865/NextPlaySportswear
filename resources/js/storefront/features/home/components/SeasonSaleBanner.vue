<script setup lang="ts">
import { computed } from 'vue';
import AppButton from '../../../components/ui/AppButton.vue';
import type { HomeSection } from '../types/home.types';
import { sectionTitle } from '../utils/homeSection';

const props=defineProps<{section?:HomeSection}>();
const background=computed(()=>props.section?.image || '/storage/storefront/home/hero-slide-real-team-gear.webp');
const title=computed(()=>sectionTitle(props.section,'SEASON SALE'));
const description=computed(()=>String(props.section?.description||'').trim()||'UP TO 20% OFF');
const label=computed(()=>String(props.section?.primary_label||'').trim()||'SHOP SALE');
const href=computed(()=>String(props.section?.primary_url||'').trim()||'/products');
const alt=computed(()=>String(props.section?.image_alt||'').trim()||'Season sale');
</script>
<template>
  <section class="np-sale">
    <img class="np-sale__image" :src="background" :alt="alt" loading="lazy"/>
    <span class="np-sale__overlay"></span>
    <div class="np-sale__content"><h2>{{ title }}</h2><p>{{ description }}</p><AppButton :href="href" size="md" hover-effect="chevrons">{{ label }}</AppButton></div>
  </section>
</template>
<style scoped>
.np-sale{position:relative;height:var(--np-home-sale-height);overflow:hidden;background:var(--np-color-navy)}.np-sale__image{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 38%}.np-sale__overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(6,31,68,.08) 0%,rgba(6,31,68,.24) 46%,rgba(6,31,68,.74) 100%)}.np-sale__content{position:absolute;left:50%;bottom:64px;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;color:var(--np-home-on-dark);text-align:center}.np-sale h2{font-family:var(--np-home-sale-title-font-family);font-size:var(--np-home-sale-title-size);line-height:var(--np-type-title-2-line-height);font-weight:var(--np-home-sale-title-weight);letter-spacing:0;text-transform:uppercase}.np-sale p{margin:10px 0 20px;font-family:var(--np-font-body);font-size:var(--np-home-sale-copy-size);font-weight:var(--np-type-body-2-weight);line-height:var(--np-type-body-2-line-height)}.np-sale :deep(.np-button){min-width:var(--np-home-sale-cta-min-width);min-height:var(--np-home-sale-cta-height);font-size:var(--np-home-sale-cta-font-size)}@media(max-width:640px){.np-sale{height:var(--np-home-sale-height-mobile)}.np-sale__content{bottom:42px}.np-sale h2{font-size:30px}.np-sale p{font-size:14px}.np-sale :deep(.np-button){min-height:var(--np-home-sale-cta-height-mobile)}}
</style>
