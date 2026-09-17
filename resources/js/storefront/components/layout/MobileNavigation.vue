<script setup lang="ts">
import { Menu, X } from 'lucide-vue-next';
import { ref } from 'vue';
const open = ref(false);
const items = [['SHOP','/products'],['SPORTS','/categories'],['MEN','/products?q=men'],['WOMEN','/products?q=women'],['KIDS','/products?q=kids'],['CUSTOM TEAMWEAR','/bulk-quote'],['EXPLORE','/about-us']];
</script>
<template>
  <div class="np-mobile-nav">
    <button type="button" :aria-expanded="open" aria-label="Open navigation" @click="open=!open"><X v-if="open" :size="22"/><Menu v-else :size="22"/></button>
    <div v-if="open" class="np-mobile-nav__panel"><a v-for="item in items" :key="item[0]" :href="item[1]">{{ item[0] }}</a></div>
  </div>
</template>
<style scoped>
.np-mobile-nav{display:none}.np-mobile-nav>button{display:grid;place-items:center;width:36px;height:36px;background:#fff;color:var(--np-color-navy-950)}
.np-mobile-nav__panel{position:absolute;z-index:50;left:0;right:0;top:100%;display:flex;flex-direction:column;background:#fff;border-top:1px solid var(--np-color-border);box-shadow:var(--np-shadow-card)}
.np-mobile-nav__panel a{padding:14px var(--np-page-gutter);border-bottom:1px solid #edf0f2;font-family:var(--np-font-display);font-size:13px;font-weight:600}
@media(max-width:1000px){.np-mobile-nav{display:block}}
</style>
