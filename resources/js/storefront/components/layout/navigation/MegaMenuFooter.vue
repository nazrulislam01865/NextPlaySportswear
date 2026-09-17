<script setup lang="ts">
import { Box, Headphones, Youtube, Instagram, Facebook, Music2 } from 'lucide-vue-next';
import DeliveryReturnsIcon from '../../common/DeliveryReturnsIcon.vue';
import UtilityActionLink from './UtilityActionLink.vue';
</script>

<template>
  <footer class="np-mega-footer">
    <div class="np-mega-footer__inner">
      <nav class="np-mega-footer__utility" aria-label="Mega menu utility links">
        <UtilityActionLink href="/account/orders">
          <template #icon><Box :size="17" :stroke-width="1.35" /></template>
          Track Your Order
        </UtilityActionLink>
        <UtilityActionLink href="/shipping-delivery">
          <template #icon><DeliveryReturnsIcon :size="17" /></template>
          Delivery &amp; Returns
        </UtilityActionLink>
        <UtilityActionLink href="/contact-us">
          <template #icon><Headphones :size="17" :stroke-width="1.35" /></template>
          Contact Support
        </UtilityActionLink>
      </nav>

      <div class="np-mega-footer__social" aria-label="Follow NextPlay">
        <span>Follow Us :</span>
        <a class="np-mega-footer__youtube" href="https://www.youtube.com/@nextplaysportswear" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><Youtube :size="19" :stroke-width="2" /></a>
        <a class="np-mega-footer__instagram" href="https://www.instagram.com/nextplaysportswear/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><Instagram :size="18" :stroke-width="2" /></a>
        <a class="np-mega-footer__facebook" href="https://www.facebook.com/nextplaysportswear" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><Facebook :size="18" :stroke-width="2" /></a>
        <a class="np-mega-footer__tiktok" href="https://www.tiktok.com/@nextplaysportswear" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><Music2 :size="18" :stroke-width="2.2" /></a>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.np-mega-footer {
  height: var(--np-mega-menu-footer-height);
  border-top: 1px solid var(--np-mega-menu-border);
  border-bottom: 1px solid var(--np-mega-menu-border);
  background: var(--np-mega-menu-bg);
}

.np-mega-footer__inner {
  width: 100%;
  max-width: var(--np-first-fold-max);
  height: 100%;
  margin-inline: auto;
  padding-inline: var(--np-first-fold-gutter);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--np-space-8);
}

.np-mega-footer__utility,
.np-mega-footer__social,
.np-mega-footer__social a {
  display: flex;
  align-items: center;
}

.np-mega-footer__utility {
  gap: var(--np-mega-menu-footer-utility-gap);
}

.np-mega-footer__utility :deep(.np-utility-action) {
  color: var(--np-mega-menu-text);
  font-family: var(--np-font-display);
  font-size: var(--np-mega-menu-footer-link-size);
  font-weight: var(--np-utility-font-weight);
}

.np-mega-footer__social {
  gap: var(--np-mega-menu-social-gap);
  color: var(--np-mega-menu-muted);
  font-family: var(--np-font-body);
  font-size: var(--np-mega-menu-social-label-size);
  font-weight: var(--np-weight-medium);
}

.np-mega-footer__social a {
  justify-content: center;
}

.np-mega-footer__youtube { color: var(--np-color-social-youtube); }
.np-mega-footer__instagram { color: var(--np-color-social-instagram); }
.np-mega-footer__facebook { color: var(--np-color-social-facebook); }
.np-mega-footer__tiktok { color: var(--np-color-social-tiktok); }
</style>
