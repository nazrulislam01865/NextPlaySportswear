import { defineStore } from 'pinia';
import { apiClient } from '../api/client';
import { API_ENDPOINTS } from '../api/endpoints';
import type { ApiEnvelope } from '../api/types';
import type { NavigationItem } from '../types/navigation';
import type { StorefrontCustomer } from '../types/customer';

interface BootstrapPayload {
  site: { name?: string; tagline?: string; logo?: string };
  customer: StorefrontCustomer | null;
  cart: { quantity?: number; total_items?: number; total?: number };
  wishlist: { total_items?: number };
  navigation: Record<string, NavigationItem[]>;
}

export const useStorefrontStore = defineStore('storefront', {
  state: () => ({
    initialized: false,
    loading: false,
    site: {} as BootstrapPayload['site'],
    customer: null as StorefrontCustomer | null,
    cartQuantity: 0,
    cartTotal: 0,
    wishlistCount: 0,
    navigation: {} as Record<string, NavigationItem[]>,
  }),
  actions: {
    async bootstrap(force = false) {
      if ((this.initialized || this.loading) && !force) return;
      this.loading = true;
      try {
        const response = await apiClient.get<ApiEnvelope<BootstrapPayload>>(API_ENDPOINTS.bootstrap);
        const payload = response.data.data;
        this.site = payload.site ?? {};
        this.customer = payload.customer ?? null;
        this.cartQuantity = Number(payload.cart?.quantity ?? payload.cart?.total_items ?? 0);
        this.cartTotal = Number(payload.cart?.total ?? 0);
        this.wishlistCount = Number(payload.wishlist?.total_items ?? 0);
        this.navigation = payload.navigation ?? {};
        this.initialized = true;
      } finally {
        this.loading = false;
      }
    },
    setCartQuantity(quantity: number) {
      this.cartQuantity = Math.max(0, Number(quantity || 0));
    },
    setCartSummary(cart: { quantity?: number; total?: number }) {
      this.cartQuantity = Math.max(0, Number(cart?.quantity ?? 0));
      this.cartTotal = Math.max(0, Number(cart?.total ?? 0));
    },
    setWishlistCount(count: number) {
      this.wishlistCount = Math.max(0, Number(count || 0));
    },
  },
});
