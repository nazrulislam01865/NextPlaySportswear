<script setup lang="ts">
import { computed } from 'vue';
import StorefrontLinkIcon from '../common/StorefrontLinkIcon.vue';
import FooterLinkColumn from './FooterLinkColumn.vue';
import BrandLogo from '../common/BrandLogo.vue';
import AppButton from '../ui/AppButton.vue';
import { useStorefrontStore } from '../../stores/storefront.store';

const storefront = useStorefrontStore();

const contact = computed(() => storefront.footer.contact ?? {});
const footerColumns = computed(() => (storefront.footer.columns ?? [])
  .filter((column) => column.enabled !== false && Boolean(column.title))
  .map((column) => ({
    title: column.title || '',
    items: (column.items ?? [])
      .filter((item) => item.enabled !== false && Boolean(item.label && item.url))
      .map((item) => ({
        label: item.label || '',
        href: item.url || '',
        icon: item.icon || null,
      })),
  }))
  .filter((column) => column.items.length > 0));
const club = computed(() => storefront.footer.club ?? {});
const social = computed(() => storefront.footer.social ?? {});
const socialLinks = computed(() => (social.value.links ?? [])
  .filter((item) => item.enabled !== false && Boolean(item.label && item.url && item.icon)));
const legal = computed(() => storefront.footer.legal ?? {});
const legalLinks = computed(() => (legal.value.links ?? [])
  .filter((item) => item.enabled !== false && Boolean(item.label && item.url)));
const payments = computed(() => storefront.footer.payments ?? {});

const phoneHref = computed(() => contact.value.phone
  ? `tel:${contact.value.phone.replace(/[^+\d]/g, '')}`
  : '');
const copyright = computed(() => (legal.value.copyright || '')
  .replace('{year}', String(new Date().getFullYear())));
const hasSocialLinks = computed(() => socialLinks.value.length > 0);
</script>

<template>
  <footer class="np-footer" aria-label="Storefront footer">
    <div class="np-footer__top">
      <div
        class="np-footer__inner np-footer__main"
        :class="{ 'np-footer__main--dynamic': footerColumns.length !== 3 }"
      >
        <section class="np-footer__brand" aria-label="NextPlay contact information">
          <BrandLogo class="np-footer__logo" />
          <p v-if="contact.address">{{ contact.address }}</p>
          <a v-if="contact.email" class="np-footer__contact" :href="`mailto:${contact.email}`">{{ contact.email }}</a>
          <a v-if="contact.phone" class="np-footer__contact" :href="phoneHref">{{ contact.phone }}</a>
        </section>

        <FooterLinkColumn
          v-for="(column, index) in footerColumns"
          :key="`${column.title}-${index}`"
          :title="column.title"
          :items="column.items"
        />

        <section v-if="club.enabled !== false && club.title && club.button_label && club.button_url" class="np-footer__signup">
          <h3>{{ club.title }}</h3>
          <AppButton class="np-footer__signup-button" :href="club.button_url" variant="orange" size="footer" hover-effect="chevrons">{{ club.button_label }}</AppButton>
        </section>
      </div>
    </div>

    <div v-if="social.enabled !== false && hasSocialLinks" class="np-footer__socialBand">
      <div class="np-footer__inner np-footer__social-row">
        <span v-if="social.label">{{ social.label }}</span>
        <nav class="np-footer__social" aria-label="Follow NextPlay">
          <a
            v-for="(item, index) in socialLinks"
            :key="`${item.url}-${item.icon}-${index}`"
            :href="item.url || '#'"
            target="_blank"
            rel="noopener noreferrer"
            :aria-label="item.label || 'Social link'"
          >
            <StorefrontLinkIcon :source="item.icon" variant="social" />
          </a>
        </nav>
      </div>
    </div>

    <div class="np-footer__bottomWrap">
      <div class="np-footer__inner np-footer__bottom">
        <div class="np-footer__legal-group">
          <span v-if="copyright" class="np-footer__copyright">{{ copyright }}</span>
          <span v-if="copyright && legalLinks.length" class="np-footer__separator" aria-hidden="true"></span>
          <nav v-if="legalLinks.length" class="np-footer__legal" aria-label="Legal links">
            <a v-for="(item, index) in legalLinks" :key="`${item.url}-${item.label}-${index}`" :href="item.url || '#'">
              <StorefrontLinkIcon v-if="item.icon" :source="item.icon" :size="13" />
              <span>{{ item.label }}</span>
            </a>
          </nav>
        </div>

        <div v-if="payments.enabled !== false" class="np-footer__payments" aria-label="Accepted payment methods">
          <span v-if="payments.label" class="np-footer__payments-label">{{ payments.label }}</span>
          <div class="np-footer__payment-logos" aria-hidden="true">
            <span class="np-pay np-pay--visa">VISA</span>
            <span class="np-pay np-pay--mc"><i></i><i></i></span>
            <span class="np-pay np-pay--amex">AMEX</span>
            <span class="np-pay np-pay--jcb">JCB</span>
            <span class="np-pay np-pay--discover">DISCOVER</span>
            <span class="np-pay np-pay--diners">D</span>
            <span class="np-pay np-pay--union">UnionPay</span>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
<style scoped>
.np-footer {
  overflow: hidden;
  border-top: 1px solid var(--np-footer-border-color);
  border-radius: 0 0 var(--np-footer-radius) var(--np-footer-radius);
  background: var(--np-footer-bg);
  color: var(--np-footer-link-color);
}

.np-footer__inner {
  width: 100%;
  max-width: var(--np-showcase-max);
  margin-inline: auto;
  box-sizing: border-box;
  padding-inline: var(--np-showcase-gutter);
}

.np-footer__main {
  display: grid;
  grid-template-columns: var(--np-footer-main-columns);
  gap: var(--np-footer-main-gap);
  padding-top: var(--np-footer-top-padding);
  padding-bottom: var(--np-footer-top-padding-bottom);
}

.np-footer__main--dynamic {
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}

.np-footer__brand {
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--np-footer-brand-gap);
}

.np-footer__logo {
  width: var(--np-footer-logo-width);
  margin-bottom: var(--np-footer-logo-gap);
}

.np-footer__logo :deep(img) {
  width: 100%;
  height: auto;
  object-fit: contain;
}

.np-footer__brand p,
.np-footer__contact {
  position: relative;
  width: fit-content;
  margin: 0;
  color: var(--np-footer-link-color);
  font-family: var(--np-font-body);
  font-size: var(--np-footer-brand-size);
  font-weight: var(--np-footer-link-weight);
  line-height: 1.35;
  text-decoration: none;
}

.np-footer__contact {
  color: var(--np-color-orange);
}

.np-footer__contact::after,
.np-footer__legal a::after {
  position: absolute;
  right: 0;
  bottom: calc(-1 * var(--np-mega-menu-underline-offset));
  left: 0;
  height: var(--np-mega-menu-underline-height);
  background: currentColor;
  content: '';
  pointer-events: none;
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform var(--np-transition-base);
}

.np-footer__contact:hover::after,
.np-footer__contact:focus-visible::after,
.np-footer__legal a:hover::after,
.np-footer__legal a:focus-visible::after {
  transform: scaleX(1);
}

.np-footer__signup {
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--np-footer-signup-gap);
}

.np-footer__signup h3 {
  max-width: 270px;
  margin: 0;
  color: var(--np-footer-link-color);
  font-family: var(--np-font-display);
  font-size: var(--np-footer-signup-title-size);
  font-weight: var(--np-footer-signup-title-weight);
  line-height: 1.15;
  text-transform: uppercase;
}

.np-footer__signup-button {
  flex: 0 0 auto;
}

.np-footer__socialBand {
  background: var(--np-footer-band-bg);
}

.np-footer__social-row {
  display: flex;
  min-height: var(--np-footer-social-band-height);
  align-items: center;
  justify-content: center;
  gap: var(--np-footer-social-label-gap);
}

.np-footer__social-row > span {
  color: var(--np-footer-link-color);
  font-family: var(--np-font-body);
  font-size: var(--np-footer-follow-size);
  font-weight: 600;
  line-height: 1;
}

.np-footer__social {
  display: flex;
  align-items: center;
  gap: var(--np-footer-social-gap);
}

.np-footer__social a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform var(--np-transition-base), opacity var(--np-transition-base);
}

.np-footer__social a:hover,
.np-footer__social a:focus-visible {
  transform: translateY(-1px);
  opacity: .86;
  outline: none;
}

.np-footer__youtube { color: var(--np-color-social-youtube); }
.np-footer__instagram { color: var(--np-color-social-instagram); }
.np-footer__facebook { color: var(--np-color-social-facebook); }
.np-footer__tiktok { color: var(--np-color-social-tiktok); }

.np-footer__bottomWrap {
  background: var(--np-footer-bottom-bg);
}

.np-footer__bottom {
  display: flex;
  min-height: var(--np-footer-bottom-height);
  align-items: center;
  justify-content: space-between;
  gap: var(--np-footer-bottom-gap);
}

.np-footer__legal-group,
.np-footer__legal,
.np-footer__payments,
.np-footer__payment-logos {
  display: flex;
  align-items: center;
}

.np-footer__legal-group {
  gap: var(--np-footer-legal-group-gap);
}

.np-footer__separator {
  width: 1px;
  height: 20px;
  background: var(--np-footer-separator-color);
}

.np-footer__legal {
  gap: var(--np-footer-legal-gap);
}

.np-footer__copyright,
.np-footer__legal a,
.np-footer__payments-label {
  color: var(--np-footer-bottom-color);
  font-family: var(--np-font-body);
  font-size: var(--np-footer-bottom-size);
  font-weight: 500;
  line-height: 1.25;
  text-decoration: none;
  white-space: nowrap;
}

.np-footer__legal a {
  position: relative;
  display: inline-flex;
  width: fit-content;
  align-items: center;
  gap: 6px;
}

.np-footer__payments {
  justify-content: flex-end;
  gap: var(--np-footer-payment-label-gap);
}

.np-footer__payment-logos {
  gap: var(--np-footer-payment-logo-gap);
}

.np-pay {
  display: inline-flex;
  min-width: 24px;
  height: 18px;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  font-family: var(--np-font-primary);
  font-size: 8px;
  font-weight: 800;
  line-height: 1;
  white-space: nowrap;
}

.np-pay--visa { color: #1639ad; font-size: 14px; font-style: italic; }
.np-pay--mc { position: relative; width: 27px; }
.np-pay--mc i { position: absolute; width: 16px; height: 16px; border-radius: 50%; top: 1px; }
.np-pay--mc i:first-child { left: 1px; background: #eb001b; }
.np-pay--mc i:last-child { right: 1px; background: #f79e1b; opacity: .92; }
.np-pay--amex { padding: 0 3px; border-radius: 1px; background: #2b7bbb; color: #fff; font-size: 7px; }
.np-pay--jcb { padding: 0 3px; border-radius: 1px; background: linear-gradient(90deg,#0b8b69 0 33%,#d61e35 33% 66%,#1c4aa7 66%); color:#fff; font-size:7px; }
.np-pay--discover { position: relative; color: #161616; font-size: 9px; }
.np-pay--discover::after { width: 8px; height: 8px; margin-left: 1px; border-radius: 50%; background: #e68b2c; content: ''; }
.np-pay--diners { width: 20px; min-width: 20px; border: 1px solid #3d61a8; border-radius: 50%; color: #3d61a8; font-size: 9px; }
.np-pay--union { padding: 0 3px; border-radius: 1px; background: linear-gradient(135deg,#dc3133 0 34%,#1a58b0 34% 67%,#14985b 67%); color:#fff; font-size:6px; }

@media (max-width: 1280px) {
  .np-footer__main {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .np-footer__signup {
    grid-column: 1 / -1;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .np-footer__signup h3 {
    max-width: none;
  }

  .np-footer__bottom {
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    padding-block: 16px;
  }

  .np-footer__payments {
    justify-content: flex-start;
  }
}

@media (max-width: 900px) {
  .np-footer__main {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .np-footer__signup {
    grid-column: 1 / -1;
  }

  .np-footer__legal-group {
    align-items: flex-start;
    flex-direction: column;
  }

  .np-footer__separator {
    display: none;
  }
}

@media (max-width: 560px) {
  .np-footer__main {
    grid-template-columns: 1fr;
  }

  .np-footer__signup {
    grid-column: auto;
    flex-direction: column;
    align-items: flex-start;
  }

  .np-footer__social-row {
    min-height: 64px;
  }

  .np-footer__legal {
    align-items: flex-start;
    flex-direction: column;
  }

  .np-footer__payments {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
