<script setup lang="ts">
import { computed, ref } from 'vue';
import { Menu, X } from 'lucide-vue-next';
import type { HeaderNavigationItemSetting } from './navigation/mega-menu.config';

const props = withDefaults(defineProps<{
  items?: HeaderNavigationItemSetting[];
}>(), {
  items: () => [],
});

const open = ref(false);
const visibleItems = computed(() => props.items.filter((item) => (
  item.enabled !== false && Boolean(item.label?.trim() && item.url?.trim())
)));
</script>

<template>
  <div class="np-mobile-nav">
    <button type="button" :aria-expanded="open" aria-label="Open navigation" @click="open = !open">
      <X v-if="open" :size="22" />
      <Menu v-else :size="22" />
    </button>
    <div v-if="open" class="np-mobile-nav__panel">
      <a
        v-for="(item, index) in visibleItems"
        :key="`${item.label}-${index}`"
        :href="item.url || '#'"
        :target="item.target || '_self'"
        :rel="item.target === '_blank' ? 'noopener noreferrer' : undefined"
      >{{ item.label }}</a>
    </div>
  </div>
</template>

<style scoped>
.np-mobile-nav{display:none}.np-mobile-nav>button{display:grid;place-items:center;width:36px;height:36px;background:#fff;color:var(--np-color-navy-950)}
.np-mobile-nav__panel{position:absolute;z-index:50;left:0;right:0;top:100%;display:flex;flex-direction:column;background:#fff;border-top:1px solid var(--np-color-border);box-shadow:var(--np-shadow-card)}
.np-mobile-nav__panel a{padding:14px var(--np-page-gutter);border-bottom:1px solid #edf0f2;font-family:var(--np-font-display);font-size:13px;font-weight:600}
@media(max-width:1000px){.np-mobile-nav{display:block}}
</style>
