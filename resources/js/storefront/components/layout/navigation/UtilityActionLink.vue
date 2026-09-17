<script setup lang="ts">
defineProps<{
  href: string;
}>();
</script>

<template>
  <a class="np-utility-action" :href="href">
    <span class="np-utility-action__icon" aria-hidden="true"><slot name="icon" /></span>
    <span class="np-utility-action__label"><slot /></span>
  </a>
</template>

<style scoped>
.np-utility-action {
  display: inline-flex;
  width: fit-content;
  align-items: center;
  gap: var(--np-space-2);
  color: inherit;
  text-decoration: none;
}

.np-utility-action__icon {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  color: var(--np-color-icon-muted);
}

.np-utility-action__label {
  position: relative;
  display: inline-block;
  white-space: nowrap;
}

.np-utility-action__label::after {
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

.np-utility-action:hover .np-utility-action__label::after,
.np-utility-action:focus-visible .np-utility-action__label::after {
  transform: scaleX(1);
}

.np-utility-action:focus-visible {
  outline: none;
}
</style>
