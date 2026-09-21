<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import StorefrontLinkIcon from '../common/StorefrontLinkIcon.vue';
import UtilityActionLink from './navigation/UtilityActionLink.vue';
import { useStorefrontStore } from '../../stores/storefront.store';

const storefront = useStorefrontStore();
const activeAnnouncementIndex = ref(0);
const dismissedAnnouncements = ref<Set<number>>(new Set());
let rotationTimer: ReturnType<typeof setInterval> | null = null;

const announcements = computed(() => (storefront.header.announcements ?? [])
  .map((item, sourceIndex) => ({ ...item, sourceIndex }))
  .filter((item) => item.enabled !== false && Boolean(item.text) && !dismissedAnnouncements.value.has(item.sourceIndex)));

const activeAnnouncement = computed(() => {
  if (!announcements.value.length) return null;
  return announcements.value[activeAnnouncementIndex.value % announcements.value.length] ?? null;
});

const utilityLinks = computed(() => (storefront.header.utility_links ?? [])
  .filter((item) => item.enabled !== false && Boolean(item.label && item.url)));

const hasUtilityLinks = computed(() => utilityLinks.value.length > 0);
const isVisible = computed(() => Boolean(activeAnnouncement.value) || hasUtilityLinks.value);

const rotateAnnouncement = () => {
  if (announcements.value.length > 1) {
    activeAnnouncementIndex.value = (activeAnnouncementIndex.value + 1) % announcements.value.length;
  }
};

const dismissAnnouncement = () => {
  const current = activeAnnouncement.value;
  if (!current) return;

  const next = new Set(dismissedAnnouncements.value);
  next.add(current.sourceIndex);
  dismissedAnnouncements.value = next;
};

watch(() => announcements.value.length, (length) => {
  if (!length || activeAnnouncementIndex.value >= length) {
    activeAnnouncementIndex.value = 0;
  }
});

onMounted(() => {
  rotationTimer = setInterval(rotateAnnouncement, 6000);
});

onBeforeUnmount(() => {
  if (rotationTimer) clearInterval(rotationTimer);
});
</script>

<template>
  <div v-if="isVisible" class="np-utility-bar">
    <div class="np-utility-bar__inner">
      <div v-if="activeAnnouncement" class="np-utility-bar__offer" aria-live="polite">
        <a v-if="activeAnnouncement.url" :href="activeAnnouncement.url">{{ activeAnnouncement.text }}</a>
        <span v-else>{{ activeAnnouncement.text }}</span>
        <button
          v-if="activeAnnouncement.dismissible !== false"
          type="button"
          class="np-utility-bar__close-button"
          aria-label="Dismiss announcement"
          @click="dismissAnnouncement"
        >
          <X class="np-utility-bar__close" :size="15" :stroke-width="1.5" aria-hidden="true" />
        </button>
      </div>
      <nav v-if="hasUtilityLinks" aria-label="Utility navigation">
        <UtilityActionLink
          v-for="(item, index) in utilityLinks"
          :key="`${item.url}-${item.label}-${index}`"
          :href="item.url || '/'"
        >
          <template v-if="item.icon" #icon><StorefrontLinkIcon :source="item.icon" :size="16" /></template>
          {{ item.label }}
        </UtilityActionLink>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.np-utility-bar {
  min-height: var(--np-utility-height);
  border-bottom: 1px solid var(--np-color-header-border);
  background: var(--np-color-utility-bg);
  color: var(--np-color-navy-950);
  font-family: var(--np-font-display);
  font-size: var(--np-utility-font-size);
  font-weight: 600;
}
.np-utility-bar__inner {
  width: 100%;
  max-width: var(--np-first-fold-max);
  min-height: var(--np-utility-height);
  margin: 0 auto;
  padding-inline: var(--np-first-fold-gutter);
  box-sizing: border-box;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--np-space-5);
}
.np-utility-bar__offer,
.np-utility-bar nav {
  display: flex;
  align-items: center;
}
.np-utility-bar__offer {
  min-width: 0;
  gap: var(--np-space-2);
}
.np-utility-bar__offer a,
.np-utility-bar__offer span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.np-utility-bar__offer a { color: inherit; text-decoration: none; }
.np-utility-bar nav {
  min-width: 0;
  gap: clamp(16px, 2vw, 38px);
  overflow-x: auto;
  scrollbar-width: none;
  white-space: nowrap;
}
.np-utility-bar nav::-webkit-scrollbar { display: none; }
.np-utility-bar__close-button {
  display: inline-flex;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}
.np-utility-bar__close { flex: 0 0 auto; }
@media (max-width: 900px) {
  .np-utility-bar__inner { justify-content: center; }
  .np-utility-bar nav { display: none; }
}
@media (max-width: 560px) {
  .np-utility-bar__inner { padding-inline: 16px; }
}
</style>
