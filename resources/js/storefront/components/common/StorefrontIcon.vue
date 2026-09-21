<script setup lang="ts">
import { computed, type Component } from 'vue';
import {
  CircleHelp,
  Clock,
  ExternalLink,
  FileText,
  Gift,
  Headphones,
  Heart,
  Info,
  Mail,
  MapPin,
  Package,
  Phone,
  Search,
  ShieldCheck,
  ShoppingCart,
  Tag,
  Truck,
  User,
} from 'lucide-vue-next';
import DeliveryReturnsIcon from './DeliveryReturnsIcon.vue';

const props = withDefaults(defineProps<{
  name?: string | null;
  size?: number;
}>(), {
  name: null,
  size: 16,
});

const icons: Record<string, Component> = {
  package: Package,
  headphones: Headphones,
  phone: Phone,
  mail: Mail,
  'map-pin': MapPin,
  clock: Clock,
  'help-circle': CircleHelp,
  info: Info,
  'external-link': ExternalLink,
  tag: Tag,
  gift: Gift,
  search: Search,
  user: User,
  heart: Heart,
  'shopping-cart': ShoppingCart,
  truck: Truck,
  'shield-check': ShieldCheck,
  'file-text': FileText,
};

const iconComponent = computed(() => icons[props.name || ''] ?? null);
</script>

<template>
  <DeliveryReturnsIcon v-if="name === 'delivery'" :size="size" aria-hidden="true" />
  <component v-else-if="iconComponent" :is="iconComponent" :size="size" :stroke-width="1.45" aria-hidden="true" />
</template>
