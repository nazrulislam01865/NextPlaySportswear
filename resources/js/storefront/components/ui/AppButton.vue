<script setup lang="ts">
const props = withDefaults(defineProps<{
  href?: string;
  target?: string;
  variant?: 'orange' | 'navy' | 'outline-light' | 'outline-dark' | 'ghost';
  size?: 'sm' | 'md' | 'header' | 'hero' | 'footer';
  hoverEffect?: 'none' | 'chevrons';
  disabled?: boolean;
  loading?: boolean;
}>(), {
  href: '',
  target: '_self',
  variant: 'orange',
  size: 'md',
  hoverEffect: 'none',
  disabled: false,
  loading: false,
});

const buttonClasses = () => [
  `np-button--${props.variant}`,
  `np-button--${props.size}`,
  { 'np-button--hover-chevrons': props.hoverEffect === 'chevrons' },
];
</script>

<template>
  <a
    v-if="href"
    class="np-button"
    :class="buttonClasses()"
    :href="href"
    :target="target"
  >
    <span class="np-button__label"><slot /></span>
    <span v-if="hoverEffect === 'chevrons'" class="np-button__chevrons" aria-hidden="true">
      <span class="np-button__chevron"></span>
      <span class="np-button__chevron"></span>
      <span class="np-button__chevron"></span>
    </span>
  </a>
  <button
    v-else
    type="button"
    class="np-button"
    :class="buttonClasses()"
    :disabled="disabled || loading"
  >
    <span class="np-button__label"><slot /></span>
    <span v-if="hoverEffect === 'chevrons'" class="np-button__chevrons" aria-hidden="true">
      <span class="np-button__chevron"></span>
      <span class="np-button__chevron"></span>
      <span class="np-button__chevron"></span>
    </span>
  </button>
</template>

<style scoped>
.np-button {
  --np-button-chevron-color: var(--np-button-chevron-on-transparent);
  position: relative;
  overflow: hidden;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid transparent;
  border-radius: 0;
  font-family: var(--np-font-display);
  font-weight: var(--np-weight-semibold);
  line-height: 1;
  text-transform: uppercase;
  letter-spacing: 0;
  white-space: nowrap;
  cursor: pointer;
  transition: background var(--np-transition-fast), color var(--np-transition-fast), border-color var(--np-transition-fast);
}
.np-button__label { position: relative; z-index: 1; }
.np-button__chevrons {
  position: absolute;
  z-index: 2;
  inset: 0 auto 0 0;
  display: inline-flex;
  align-items: stretch;
  pointer-events: none;
  transform: translateX(var(--np-button-chevron-start));
  transition:
    left var(--np-button-chevron-duration) var(--np-button-chevron-easing),
    transform var(--np-button-chevron-duration) var(--np-button-chevron-easing);
  will-change: left, transform;
}
.np-button__chevron {
  display: block;
  width: var(--np-button-chevron-width);
  height: 100%;
  flex: 0 0 var(--np-button-chevron-width);
  background: var(--np-button-chevron-color);
  clip-path: polygon(0 0, 56% 0, 100% 50%, 56% 100%, 0 100%, 44% 50%);
}
.np-button__chevron + .np-button__chevron {
  margin-left: calc(var(--np-button-chevron-overlap) * -1);
}
.np-button--hover-chevrons:hover .np-button__chevrons,
.np-button--hover-chevrons:focus-visible .np-button__chevrons {
  left: 100%;
  transform: translateX(var(--np-button-chevron-end-offset));
}
.np-button--md { min-width: 116px; min-height: 42px; padding: 0 20px; font-size: 11px; }
.np-button--sm { min-width: 96px; min-height: 34px; padding: 0 15px; font-size: 10px; }
.np-button--footer {
  width: var(--np-footer-signup-button-width);
  min-width: var(--np-footer-signup-button-width);
  height: var(--np-footer-signup-button-height);
  min-height: var(--np-footer-signup-button-height);
  padding: 0 14px;
  font-family: var(--np-font-display);
  font-size: var(--np-footer-signup-button-size);
  font-weight: var(--np-footer-signup-button-weight);
}
.np-button--header {
  width: var(--np-header-cta-width);
  min-width: var(--np-header-cta-width);
  height: var(--np-header-cta-height);
  min-height: var(--np-header-cta-height);
  padding: 0 var(--np-header-cta-padding-inline);
  font-family: var(--np-font-display);
  font-size: var(--np-header-cta-font-size);
  font-weight: var(--np-header-cta-font-weight);
}
.np-button--hero {
  min-width: var(--np-home-hero-action-min-width);
  height: var(--np-home-hero-action-height);
  min-height: var(--np-home-hero-action-height);
  padding: 0 var(--np-home-hero-action-padding-inline);
  font-family: var(--np-home-hero-action-font-family);
  font-size: var(--np-home-hero-action-size);
  font-weight: var(--np-home-hero-action-weight);
}
.np-button--orange {
  --np-button-chevron-color: var(--np-button-chevron-on-orange);
  background: var(--np-color-orange);
  color: var(--np-color-white);
}
.np-button--orange:hover { background: var(--np-color-orange-hover); }
.np-button--navy {
  --np-button-chevron-color: var(--np-button-chevron-on-navy);
  background: var(--np-color-navy-950);
  color: var(--np-color-white);
}
.np-button--header.np-button--navy { background: var(--np-header-cta-bg); }
.np-button--outline-light {
  --np-button-chevron-color: var(--np-button-chevron-on-transparent);
  border-color: var(--np-home-outline-light-border);
  background: var(--np-home-outline-light-bg);
  color: var(--np-color-white);
}
.np-button--outline-dark { border-color: var(--np-color-navy-950); background: var(--np-color-white); color: var(--np-color-navy-950); }
.np-button--ghost { min-width: 0; padding-inline: 8px; background: transparent; color: inherit; }
.np-button:disabled { opacity: .55; cursor: wait; }

@media (prefers-reduced-motion: reduce) {
  .np-button__chevrons { transition: none; }
}

@media (max-width: 700px) {
  .np-button--hero {
    min-width: var(--np-home-hero-action-min-width-mobile);
    height: var(--np-home-hero-action-height-mobile);
    min-height: var(--np-home-hero-action-height-mobile);
    padding-inline: var(--np-home-hero-action-padding-inline-mobile);
    font-size: var(--np-home-hero-action-size-mobile);
  }
}
</style>
