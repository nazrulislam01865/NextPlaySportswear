<script setup lang="ts">
export interface BreadcrumbItem {
  label: string;
  href?: string;
}

defineProps<{ items: BreadcrumbItem[] }>();
</script>

<template>
  <nav class="np-breadcrumbs" aria-label="Breadcrumb">
    <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
      <span v-if="index" class="np-breadcrumbs__separator" aria-hidden="true">/</span>
      <a v-if="item.href" class="np-breadcrumbs__link" :href="item.href">{{ item.label }}</a>
      <span v-else class="np-breadcrumbs__current" aria-current="page">{{ item.label }}</span>
    </template>
  </nav>
</template>

<style scoped>
.np-breadcrumbs {
  display: flex;
  align-items: center;
  gap: var(--np-breadcrumb-gap);
  color: var(--np-color-navy-950);
  font-family: var(--np-font-body);
  font-size: var(--np-breadcrumb-size);
  font-weight: var(--np-breadcrumb-weight);
  line-height: var(--np-breadcrumb-line-height);
}
.np-breadcrumbs__link {
  position: relative;
  width: fit-content;
  color: inherit;
}

.np-breadcrumbs__link::after {
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

.np-breadcrumbs__link:hover::after,
.np-breadcrumbs__link:focus-visible::after {
  transform: scaleX(1);
}

.np-breadcrumbs__separator,
.np-breadcrumbs__current { color: var(--np-breadcrumb-muted); }
</style>
