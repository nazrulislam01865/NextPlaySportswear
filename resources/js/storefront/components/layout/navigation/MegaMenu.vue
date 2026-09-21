<script setup lang="ts">
import type { MegaMenuSetting } from './mega-menu.config';
import MegaMenuColumn from './MegaMenuColumn.vue';
import MegaMenuTopChoices from './MegaMenuTopChoices.vue';
import MegaMenuPromo from './MegaMenuPromo.vue';
import MegaMenuFooter from './MegaMenuFooter.vue';

withDefaults(defineProps<{
  config: MegaMenuSetting;
  menuLabel?: string;
}>(), {
  menuLabel: 'Menu',
});
</script>

<template>
  <div class="np-mega-menu" role="region" :aria-label="`${menuLabel} mega menu`">
    <div class="np-mega-menu__body">
      <MegaMenuTopChoices
        v-if="config.top_choices?.enabled !== false"
        :top-choices="config.top_choices"
      />

      <div class="np-mega-menu__columns">
        <MegaMenuColumn
          v-for="(column, index) in (config.columns || []).filter((entry) => entry.enabled !== false && Boolean(entry.title))"
          :key="`${column.title}-${index}`"
          :column="column"
        />
      </div>

      <MegaMenuPromo v-if="config.promo?.enabled !== false" :promo="config.promo" />
    </div>
    <MegaMenuFooter />
  </div>
</template>

<style scoped>
.np-mega-menu {
  position: absolute;
  z-index: var(--np-mega-menu-z-index);
  top: 100%;
  left: 0;
  right: 0;
  background: var(--np-mega-menu-bg);
  color: var(--np-mega-menu-text);
  box-shadow: var(--np-mega-menu-shadow);
}

.np-mega-menu__body {
  display: grid;
  width: 100%;
  max-width: var(--np-first-fold-max);
  height: var(--np-mega-menu-height);
  margin-inline: auto;
  grid-template-columns: var(--np-mega-menu-rail-width) minmax(0, 1fr) var(--np-mega-menu-promo-width);
  background: var(--np-mega-menu-bg);
}

.np-mega-menu__columns {
  display: grid;
  min-width: 0;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  padding-inline: var(--np-mega-menu-columns-padding);
  column-gap: var(--np-mega-menu-column-gap);
}

@media (max-width: 1500px) {
  .np-mega-menu__body {
    grid-template-columns: var(--np-mega-menu-rail-width-compact) minmax(0, 1fr) var(--np-mega-menu-promo-width-compact);
  }
  .np-mega-menu__columns {
    padding-inline: var(--np-mega-menu-columns-padding-compact);
    column-gap: var(--np-mega-menu-column-gap-compact);
  }
}

@media (max-width: 1000px) {
  .np-mega-menu { display: none; }
}
</style>
