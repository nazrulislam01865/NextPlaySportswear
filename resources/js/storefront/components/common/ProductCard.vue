<script setup lang="ts">
import { computed } from 'vue';
import { Heart } from 'lucide-vue-next';
import PriceDisplay from './PriceDisplay.vue';
import type { StorefrontProduct } from '../../types/product';

const props = withDefaults(defineProps<{
  product: StorefrontProduct;
  busy?: boolean;
  forceCustomize?: boolean;
  variant?: 'default' | 'new-arrivals';
  showPrice?: boolean;
  showBadges?: boolean;
}>(), { busy: false, forceCustomize: false, variant: 'default', showPrice: true, showBadges: true });

const emit = defineEmits<{ add: [product: StorefrontProduct]; wishlist: [product: StorefrontProduct] }>();

const metaLine = computed(() => {
  const hierarchy = [props.product.category, props.product.subcategory]
    .filter((value): value is string => Boolean(value))
    .filter((value, index, values) => values.findIndex((candidate) => candidate.toLowerCase() === value.toLowerCase()) === index);

  if (hierarchy.length < 2 && props.product.sport) {
    const sport = props.product.sport;
    if (!hierarchy.some((value) => value.toLowerCase() === sport.toLowerCase())) hierarchy.push(sport);
  }

  return hierarchy.length ? hierarchy.join(' · ') : 'Sports Uniforms';
});

type ProductGalleryImage = string | { url?: string | null; alt?: string | null };
const galleryImage = (item?: ProductGalleryImage | null) => typeof item === 'string'
  ? { url: item, alt: props.product.alt || props.product.title || props.product.short_title || 'Product' }
  : { url: item?.url || '', alt: item?.alt || props.product.alt || props.product.title || props.product.short_title || 'Product' };

const primaryImage = computed(() => {
  const firstGallery = galleryImage(props.product.gallery?.[0]);
  return {
    url: props.product.image || firstGallery.url || '/images/product-placeholder.svg',
    alt: props.product.alt || firstGallery.alt,
  };
});

const secondaryImage = computed(() => {
  const primaryUrl = primaryImage.value.url;
  const second = (props.product.gallery || [])
    .map((item) => galleryImage(item))
    .find((item) => item.url && item.url !== primaryUrl);

  return second || primaryImage.value;
});

const hasActiveDiscount = computed(() => {
  const discountPrice = Number(props.product.discount_price);
  const originalPrice = Number(props.product.original_price ?? props.product.display_compare_at_price);

  return Number.isFinite(discountPrice)
    && discountPrice > 0
    && Number.isFinite(originalPrice)
    && originalPrice > discountPrice;
});

const actionLabel = computed(() => (props.forceCustomize || props.product.is_customizable) ? 'CUSTOMIZE' : 'ADD TO CART');
</script>

<template>
  <article class="np-product-card" :class="`np-product-card--${variant}`">
    <div class="np-product-card__media">
      <a class="np-product-card__media-link" :href="product.url || `/products/${product.slug}`">
        <img
          class="np-product-card__image np-product-card__image--primary"
          :src="primaryImage.url"
          :alt="primaryImage.alt"
          loading="lazy"
        />
        <img
          class="np-product-card__image np-product-card__image--secondary"
          :src="secondaryImage.url"
          :alt="secondaryImage.alt"
          loading="lazy"
          aria-hidden="true"
        />
      </a>

      <button type="button" class="np-product-card__wish" aria-label="Add to wishlist" :disabled="busy" @click="emit('wishlist', product)">
        <Heart :size="variant === 'new-arrivals' ? 17 : 15" :stroke-width="1.5" />
      </button>

      <div v-if="showBadges && variant === 'new-arrivals'" class="np-product-card__badges np-product-card__badges--prototype">
        <span class="np-pill np-pill--accent">New</span>
        <span class="np-pill">Offer</span>
        <span class="np-pill">Popular</span>
      </div>
      <div v-else-if="showBadges" class="np-product-card__badges">
        <span v-if="product.discount_percentage" class="np-badge np-badge--orange">-{{ product.discount_percentage }}%</span>
        <span v-if="product.tag" class="np-badge">{{ product.tag }}</span>
      </div>
    </div>

    <div class="np-product-card__body">
      <p class="np-product-card__meta">{{ metaLine }}</p>
      <a class="np-product-card__title np-clamp-2" :href="product.url || `/products/${product.slug}`">{{ product.short_title || product.title }}</a>
      <PriceDisplay
        v-if="showPrice"
        :price="product.price"
        :value="product.discount_price ?? product.display_unit_price"
        :original="product.original_price ?? product.display_compare_at_price"
        :original-label="product.original_price_label || product.compare_at_price_label"
        :discount-active="hasActiveDiscount"
        :currency="product.currency"
      />
    </div>

    <button type="button" class="np-product-card__action" :disabled="busy" @click="emit('add', product)">{{ actionLabel }}</button>
  </article>
</template>

<style scoped>
.np-product-card {
  display:flex;
  min-width:0;
  height:100%;
  flex-direction:column;
  background:var(--np-color-white);
  transition:border-color var(--np-transition-fast), box-shadow var(--np-transition-fast), transform var(--np-transition-fast);
}

.np-product-card__media {
  position:relative;
  overflow:hidden;
}

.np-product-card__media-link {
  position:absolute;
  inset:0;
  display:block;
}

.np-product-card__image {
  position:absolute;
  inset:0;
  width:100%;
  height:100%;
  display:block;
  transition:opacity var(--np-home-product-image-transition);
}

.np-product-card__image--primary { opacity:1; }
.np-product-card__image--secondary { opacity:0; }

.np-product-card__wish {
  position:absolute;
  display:grid;
  place-items:center;
  color:var(--np-color-navy-950);
  cursor:pointer;
  transition:background var(--np-transition-fast), color var(--np-transition-fast), border-color var(--np-transition-fast);
}

.np-product-card__badges {
  position:absolute;
  display:flex;
  flex-wrap:wrap;
  gap:3px;
}

.np-badge {
  padding:3px 5px;
  background:var(--np-color-white);
  color:var(--np-home-text-primary);
  font-size:var(--np-home-badge-size);
  font-weight:var(--np-weight-semibold);
  line-height:1;
  text-transform:uppercase;
}

.np-badge--orange { background:#fff3ee; color:var(--np-color-orange); }

.np-product-card__body {
  display:flex;
  flex:1;
  flex-direction:column;
}

.np-product-card__meta { color:var(--np-home-text-muted); }
.np-product-card__title { color:var(--np-home-text-body); }
.np-product-card__action {
  cursor:pointer;
  transition:background var(--np-transition-fast), color var(--np-transition-fast), border-color var(--np-transition-fast);
}

/* Default card styling (used by other sections). */
.np-product-card--default { border-right:1px solid #eef1f4; }
.np-product-card--default .np-product-card__media { aspect-ratio:.78; background:#f1f3f4; }
.np-product-card--default .np-product-card__image { object-fit:cover; }
.np-product-card--default .np-product-card__wish { top:8px; right:8px; width:27px; height:27px; border:1px solid #dfe4e8; background:rgba(255,255,255,.94); }
.np-product-card--default .np-product-card__badges { left:7px; bottom:8px; }
.np-product-card--default .np-product-card__body { min-height:108px; gap:6px; padding:11px 9px 12px; }
.np-product-card--default .np-product-card__meta { font-size:8px; }
.np-product-card--default .np-product-card__title { min-height:34px; font-size:10px; font-weight:500; line-height:1.55; }
.np-product-card--default .np-product-card__action { min-height:34px; background:#f3f5f7; color:var(--np-color-navy-950); font-family:var(--np-font-display); font-size:9px; font-weight:600; text-transform:uppercase; }
.np-product-card--default .np-product-card__action:hover { background:var(--np-color-navy-950); color:#fff; }

/* Prototype-matched New Arrivals card styling. */
.np-product-card--new-arrivals {
  border:1px solid var(--np-product-card-border);
  box-shadow:none;
}
.np-product-card--new-arrivals:hover,
.np-product-card--new-arrivals:focus-within {
  border-color:var(--np-color-orange);
}
.np-product-card--new-arrivals .np-product-card__media {
  aspect-ratio:0.86;
  background:var(--np-home-card-media-bg);
}
.np-product-card--new-arrivals .np-product-card__image {
  object-fit:contain;
  object-position:center top;
  padding:0;
}

.np-product-card--new-arrivals:hover .np-product-card__image--primary,
.np-product-card--new-arrivals:focus-within .np-product-card__image--primary {
  opacity:0;
}
.np-product-card--new-arrivals:hover .np-product-card__image--secondary,
.np-product-card--new-arrivals:focus-within .np-product-card__image--secondary {
  opacity:1;
}

.np-product-card--new-arrivals .np-product-card__wish {
  top:14px;
  right:14px;
  width:40px;
  height:40px;
  border:1px solid #e5e9ee;
  background:var(--np-color-white);
}
.np-product-card--new-arrivals .np-product-card__wish:hover {
  border-color:var(--np-color-navy-950);
  background:var(--np-color-navy-950);
  color:var(--np-home-on-dark);
}
.np-product-card__badges--prototype {
  left:16px;
  bottom:12px;
  gap:0;
}
.np-pill {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-height:24px;
  padding:0 10px;
  border:1px solid #e6eaef;
  background:var(--np-color-white);
  color:var(--np-color-navy-950);
  font-family:var(--np-font-body);
  font-size:var(--np-home-badge-size);
  font-weight:var(--np-weight-medium);
  line-height:1;
}
.np-pill--accent { color:var(--np-color-orange); }
.np-product-card--new-arrivals .np-product-card__body {
  min-height:var(--np-home-product-body-min-height);
  gap:10px;
  padding:20px 20px 22px;
}
.np-product-card--new-arrivals .np-product-card__meta {
  font-family:var(--np-font-body);
  font-size:var(--np-home-product-meta-size);
  font-weight:var(--np-weight-regular);
  line-height:1.4;
  color:var(--np-product-card-meta);
}
.np-product-card--new-arrivals .np-product-card__title {
  display:-webkit-box;
  min-height:46px;
  overflow:hidden;
  -webkit-box-orient:vertical;
  -webkit-line-clamp:2;
  font-family:var(--np-font-body);
  font-size:var(--np-home-product-title-size);
  font-weight:var(--np-home-product-title-weight);
  line-height:1.45;
  letter-spacing:0;
  color:var(--np-product-card-title);
}
.np-product-card--new-arrivals :deep(.np-price) {
  gap:10px;
  width:100%;
  margin-top:auto;
  padding-top:18px;
  border-top:1px solid var(--np-home-divider);
}
.np-product-card--new-arrivals :deep(.np-price strong) {
  font-family:var(--np-font-display);
  font-size:var(--np-home-product-price-size);
  font-weight:var(--np-home-product-price-weight);
  line-height:1;
  color:var(--np-color-navy-950);
}
.np-product-card--new-arrivals :deep(.np-price del) {
  font-family:var(--np-font-display);
  font-size:var(--np-home-product-compare-price-size);
  color:#8e99ab;
}
.np-product-card--new-arrivals .np-product-card__action {
  min-height:var(--np-home-product-action-height);
  border-top:1px solid #e6ebf0;
  background:var(--np-product-card-action-bg);
  color:var(--np-color-navy-950);
  font-family:var(--np-font-display);
  font-size:var(--np-home-product-action-size);
  font-weight:var(--np-home-product-action-weight);
  text-transform:uppercase;
}
.np-product-card--new-arrivals:hover .np-product-card__action,
.np-product-card--new-arrivals:focus-within .np-product-card__action {
  background:var(--np-color-orange);
  border-color:var(--np-color-orange);
  color:var(--np-home-on-dark);
}

.np-product-card--new-arrivals .np-product-card__body:has(.np-product-card__meta + .np-product-card__title:last-child) {
  min-height:118px;
}

@media(max-width:640px){
  .np-product-card--default .np-product-card__body{min-height:100px}
  .np-product-card--default .np-product-card__title{font-size:11px}

  .np-product-card--new-arrivals .np-product-card__wish { top:10px; right:10px; width:36px; height:36px; }
  .np-product-card__badges--prototype { left:10px; bottom:9px; }
  .np-pill { min-height:22px; padding:0 8px; font-size:9px; }
  .np-product-card--new-arrivals .np-product-card__body { min-height:var(--np-home-product-body-min-height-mobile); padding:14px 12px 16px; gap:8px; }
  .np-product-card--new-arrivals .np-product-card__title { min-height:38px; font-size:13px; }
  .np-product-card--new-arrivals .np-product-card__action { min-height:44px; font-size:13px; }
}
</style>
