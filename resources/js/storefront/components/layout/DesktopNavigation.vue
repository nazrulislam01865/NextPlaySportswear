<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import MegaMenu from './navigation/MegaMenu.vue';
import type { HeaderNavigationItemSetting } from './navigation/mega-menu.config';

const props = withDefaults(defineProps<{
  items?: HeaderNavigationItemSetting[];
}>(), {
  items: () => [],
});

const root = ref<HTMLElement | null>(null);
const activeMenu = ref<number | null>(null);
let closeTimer: ReturnType<typeof setTimeout> | null = null;

const visibleItems = computed(() => props.items.filter((item) => (
  item.enabled !== false && Boolean(item.label?.trim() && item.url?.trim())
)));

const activeItem = computed(() => (
  activeMenu.value === null ? null : visibleItems.value[activeMenu.value] ?? null
));

const menuConfig = computed(() => {
  const megaMenu = activeItem.value?.mega_menu;
  return megaMenu?.enabled === true ? megaMenu : null;
});

function cancelClose() {
  if (closeTimer) {
    clearTimeout(closeTimer);
    closeTimer = null;
  }
}

function openMenu(index: number, item: HeaderNavigationItemSetting) {
  cancelClose();
  activeMenu.value = item.mega_menu?.enabled === true ? index : null;
}

function closeMenu() {
  cancelClose();
  activeMenu.value = null;
}

function scheduleClose() {
  cancelClose();
  closeTimer = setTimeout(closeMenu, 120);
}

function handleFocusOut(event: FocusEvent) {
  const next = event.relatedTarget as Node | null;
  if (next && root.value?.contains(next)) return;
  scheduleClose();
}

function handlePointerDown(event: PointerEvent) {
  const target = event.target as Node | null;
  if (target && !root.value?.contains(target)) closeMenu();
}

onMounted(() => document.addEventListener('pointerdown', handlePointerDown));
onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', handlePointerDown);
  cancelClose();
});
</script>

<template>
  <nav
    ref="root"
    class="np-desktop-nav"
    aria-label="Primary navigation"
    @keydown.esc="closeMenu"
    @focusin="cancelClose"
    @focusout="handleFocusOut"
    @mouseleave="scheduleClose"
  >
    <div class="np-desktop-nav__list">
      <a
        v-for="(item, index) in visibleItems"
        :key="`${item.label}-${index}`"
        class="np-desktop-nav__link"
        :class="{ 'np-desktop-nav__link--active': activeMenu === index && item.mega_menu?.enabled === true }"
        :href="item.url || '#'"
        :target="item.target || '_self'"
        :rel="item.target === '_blank' ? 'noopener noreferrer' : undefined"
        :aria-expanded="item.mega_menu?.enabled === true ? activeMenu === index : undefined"
        :aria-controls="item.mega_menu?.enabled === true ? `mega-menu-${index}` : undefined"
        @mouseenter="openMenu(index, item)"
        @focus="openMenu(index, item)"
      >{{ item.label }}</a>
    </div>

    <MegaMenu
      v-if="menuConfig && activeMenu !== null"
      :id="`mega-menu-${activeMenu}`"
      :config="menuConfig"
      :menu-label="activeItem?.label || 'Menu'"
      @mouseenter="cancelClose"
      @mouseleave="scheduleClose"
      @focusin="cancelClose"
    />
  </nav>
</template>

<style scoped>
.np-desktop-nav {
  display: flex;
  align-items: center;
  justify-content: center;
}

.np-desktop-nav__list {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--np-first-fold-nav-gap);
}

.np-desktop-nav__link {
  position: relative;
  display: inline-flex;
  min-height: var(--np-header-height);
  align-items: center;
  color: var(--np-color-navy-950);
  font-family: var(--np-font-display);
  font-size: var(--np-header-nav-font-size);
  font-weight: var(--np-header-nav-font-weight);
  line-height: 1;
  text-transform: uppercase;
  white-space: nowrap;
  transition: color var(--np-transition-fast);
}

.np-desktop-nav__link:hover,
.np-desktop-nav__link:focus-visible,
.np-desktop-nav__link--active {
  color: var(--np-color-orange);
}

.np-desktop-nav__link--active::after {
  position: absolute;
  left: 50%;
  bottom: -1px;
  width: 0;
  height: 0;
  border-right: var(--np-mega-menu-pointer-size) solid transparent;
  border-bottom: var(--np-mega-menu-pointer-height) solid var(--np-mega-menu-rail-bg);
  border-left: var(--np-mega-menu-pointer-size) solid transparent;
  content: '';
  transform: translateX(-50%);
}

@media (max-width: 1000px) {
  .np-desktop-nav { display: none; }
}
</style>
