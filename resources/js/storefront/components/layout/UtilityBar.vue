<script setup lang="ts">
import { Box, Headphones, X } from 'lucide-vue-next';
import DeliveryReturnsIcon from '../common/DeliveryReturnsIcon.vue';
import UtilityActionLink from './navigation/UtilityActionLink.vue';
</script>

<template>
  <div class="np-utility-bar">
    <div class="np-utility-bar__inner">
      <div class="np-utility-bar__offer">
        <span>Shop $200 and get free delivery</span>
        <X class="np-utility-bar__close" :size="15" :stroke-width="1.5" aria-hidden="true" />
      </div>
      <nav aria-label="Utility navigation">
        <UtilityActionLink href="/account/orders">
          <template #icon><Box :size="16" :stroke-width="1.35" /></template>
          Track Your Order
        </UtilityActionLink>
        <UtilityActionLink href="/shipping-delivery">
          <template #icon><DeliveryReturnsIcon :size="16" /></template>
          Delivery &amp; Returns
        </UtilityActionLink>
        <UtilityActionLink href="/contact-us">
          <template #icon><Headphones :size="16" :stroke-width="1.35" /></template>
          Contact Support
        </UtilityActionLink>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.np-utility-bar {
  min-height: var(--np-utility-height);
  border-bottom: 1px solid var(--np-color-header-border);
  background: var(--np-color-utility-bg);
  color: var(--np-color-utility-text);
  font-family: var(--np-font-display);
  font-size: var(--np-utility-font-size);
  font-weight: var(--np-utility-font-weight);
}
.np-utility-bar__inner {
  width: 100%;
  max-width: var(--np-first-fold-max);
  min-height: var(--np-utility-height);
  margin: 0 auto;
  padding-inline: var(--np-first-fold-gutter);
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 28px;
}
.np-utility-bar__offer,
.np-utility-bar nav {
  display: inline-flex;
  align-items: center;
}
.np-utility-bar__offer { gap: 18px; white-space: nowrap; }
.np-utility-bar__close { color: var(--np-color-utility-text); }
.np-utility-bar nav { gap: 30px; }
.np-utility-bar :deep(.np-utility-action) { gap: 9px; }

@media (max-width: 900px) {
  .np-utility-bar__inner { justify-content: center; text-align: center; }
  .np-utility-bar nav { display: none; }
}
</style>
