<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { NavigationItem } from '../../types/navigation';
import MegaMenu from './navigation/MegaMenu.vue';
import {
  DESKTOP_NAV_ITEMS,
  SHOP_MEGA_MENU,
  resolveNavigationUrl,
} from './navigation/mega-menu.config';

const props = defineProps<{ navigation?: NavigationItem[] }>();

const root = ref<HTMLElement | null>(null);
const activeMenu = ref<string | null>(null);
let closeTimer: ReturnType<typeof setTimeout> | null = null;

const menuConfig = computed(() => activeMenu.value === SHOP_MEGA_MENU.key ? SHOP_MEGA_MENU : null);

function cancelClose() {
  if (closeTimer) {
    clearTimeout(closeTimer);
    closeTimer = null;
  }
}

function openMenu(menuKey?: string) {
  cancelClose();
  activeMenu.value = menuKey ?? null;
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
        v-for="item in DESKTOP_NAV_ITEMS"
        :key="item.key"
        class="np-desktop-nav__link"
        :class="{ 'np-desktop-nav__link--active': activeMenu === item.megaMenuKey }"
        :href="resolveNavigationUrl(navigation, item.href, item.lookupLabels ?? [item.label])"
        :aria-expanded="item.megaMenuKey ? activeMenu === item.megaMenuKey : undefined"
        :aria-controls="item.megaMenuKey ? `mega-menu-${item.megaMenuKey}` : undefined"
        @mouseenter="openMenu(item.megaMenuKey)"
        @focus="openMenu(item.megaMenuKey)"
      >{{ item.label }}</a>
    </div>

    <MegaMenu
      v-if="menuConfig"
      :id="`mega-menu-${menuConfig.key}`"
      :config="menuConfig"
      :navigation="navigation"
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
