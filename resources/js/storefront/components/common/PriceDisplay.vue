<script setup lang="ts">
import { computed } from 'vue';
import { formatCurrency } from '../../utils/currency';

const props = defineProps<{
  price?: string;
  value?: number | null;
  original?: number | null;
  originalLabel?: string | null;
  discountActive?: boolean;
  currency?: string;
}>();

const cleanLegacyPrice = (value?: string | null): string => value?.replace(/^From\s+/i, '').trim() || '';
const current = computed(() => formatCurrency(props.value, props.currency || 'USD') || cleanLegacyPrice(props.price));
const original = computed(() => formatCurrency(props.original, props.currency || 'USD') || cleanLegacyPrice(props.originalLabel));
</script>

<template>
  <div class="np-price"><strong>{{ current }}</strong><del v-if="discountActive && original">{{ original }}</del></div>
</template>

<style scoped>
.np-price { display:flex; align-items:baseline; gap:7px; color:var(--np-color-navy-950); }
.np-price strong { font-size:13px; font-weight:800; }
.np-price del { font-size:10px; color:#8792a0; }
</style>
